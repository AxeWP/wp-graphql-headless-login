<?php
/**
 * Tests TokenManager::validate_token().
 *
 * @package Tests\WPGraphQL\Login\Integration\Auth
 */

namespace Tests\WPGraphQL\Login\Integration\Auth;

use Tests\WPGraphQL\Login\TestCase;
use WPGraphQL\Login\Auth\TokenManager;
use WPGraphQL\Login\Auth\User;
use WPGraphQL\Login\Vendor\Firebase\JWT\JWT;

/**
 * Tests the rejection branches of TokenManager::validate_token().
 */
class TokenManagerValidateTokenTest extends TestCase {
	/**
	 * The test user ID.
	 *
	 * @var int
	 */
	public $test_user;

	/**
	 * Adds the test site URLs and minted token issuers to the allowed `iss` domains.
	 */
	public function filterAllowedIssDomains( array $allowed_domains ): array {
		$home_url = (string) call_user_func( 'home_url' );

		return array_values(
			array_filter(
				array_unique(
					array_merge(
						$allowed_domains,
						[
							$home_url,
							$this->getBlogUrl(),
							str_replace( 'http://', 'https://', $home_url ),
							str_replace( 'https://', 'http://', $home_url ),
						]
					)
				)
			)
		);
	}

	/**
	 * Wrapper for `get_bloginfo( 'url' )`.
	 */
	private function getBlogUrl(): string {
		return (string) call_user_func( 'get_bloginfo', 'url' );
	}

	/**
	 * Builds a token payload with valid defaults, merged with the given overrides.
	 */
	private function buildPayload( array $overrides = [] ): array {
		$payload = [
			'iss'  => $this->getBlogUrl(),
			'iat'  => time(),
			'nbf'  => time(),
			'exp'  => time() + 300,
			'data' => [
				'user' => [
					'id' => $this->test_user,
				],
			],
		];

		return array_replace_recursive( $payload, $overrides );
	}

	/**
	 * Signs a payload, using the site secret key unless another key is provided.
	 */
	private function mintToken( array $payload, ?string $key = null ): string {
		JWT::$leeway = 60;

		return JWT::encode( $payload, $key ?? TokenManager::get_secret_key(), 'HS256' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function setUp(): void {
		parent::setUp();

		add_filter( 'graphql_login_iss_allowed_domains', [ $this, 'filterAllowedIssDomains' ] );

		$this->test_user = $this->factory()->user->create(
			[
				'role' => 'administrator',
			]
		);

		// Seeds the site secret key and the user secret.
		$this->generate_user_tokens( $this->test_user );

		unset( $_SERVER['HTTP_AUTHORIZATION'] );
	}

	/**
	 * {@inheritDoc}
	 */
	public function tearDown(): void {
		unset( $_SERVER['HTTP_AUTHORIZATION'] );
		User::set_is_secret_revoked( $this->test_user, false );
		call_user_func( 'remove_all_filters', 'graphql_login_iss_allowed_domains' );
		wp_set_current_user( 0 );
		$this->reset_utils_properties();

		parent::tearDown();
	}

	/**
	 * Tests that a token from an unallowed issuer is rejected.
	 */
	public function test_rejects_wrong_issuer(): void {
		$payload = $this->buildPayload(
			[
				'iss' => 'https://evil.example',
			]
		);

		$result = TokenManager::validate_token( $this->mintToken( $payload ) );

		$this->assertWPError( $result );
		$this->assertSame( 'invalid-jwt', $result->get_error_code() );
	}

	/**
	 * Tests that an expired token is rejected.
	 */
	public function test_rejects_expired_token(): void {
		// Well beyond the 60s JWT::$leeway used by the validator.
		$payload = $this->buildPayload(
			[
				'iat' => time() - ( 2 * HOUR_IN_SECONDS ),
				'nbf' => time() - ( 2 * HOUR_IN_SECONDS ),
				'exp' => time() - HOUR_IN_SECONDS,
			]
		);

		$result = TokenManager::validate_token( $this->mintToken( $payload ) );

		$this->assertWPError( $result );
		$this->assertSame( 'invalid-secret-key', $result->get_error_code() );
	}

	/**
	 * Tests that a token with a tampered signature is rejected.
	 */
	public function test_rejects_tampered_signature(): void {
		$payload = $this->buildPayload();

		// The JWT library requires a minimum HMAC key length, so pad the wrong key.
		$wrong_key = str_repeat( 'not-the-real-key', 4 );

		$result = TokenManager::validate_token( $this->mintToken( $payload, $wrong_key ) );

		$this->assertWPError( $result );
		$this->assertSame( 'invalid-secret-key', $result->get_error_code() );
	}

	/**
	 * Tests that a refresh token cannot be used as an auth token.
	 */
	public function test_rejects_refresh_token_used_as_auth_token(): void {
		$payload = $this->buildPayload(
			[
				'data' => [
					'user' => [
						'user_secret' => TokenManager::get_user_secret( $this->test_user, false ),
					],
				],
			]
		);

		// Validated as an auth token, i.e. `$is_refresh_token = false`.
		$result = TokenManager::validate_token( $this->mintToken( $payload ), false );

		$this->assertWPError( $result );
		$this->assertSame( 'invalid-jwt', $result->get_error_code() );
	}

	/**
	 * Tests that a token without a user ID is rejected.
	 */
	public function test_rejects_missing_user_id(): void {
		$payload = $this->buildPayload(
			[
				'data' => [
					'user' => [
						'id' => null,
					],
				],
			]
		);

		$result = TokenManager::validate_token( $this->mintToken( $payload ) );

		$this->assertWPError( $result );
		$this->assertSame( 'invalid-jwt', $result->get_error_code() );
	}

	/**
	 * Tests that a refresh token is rejected when the user has no secret.
	 */
	public function test_refresh_rejects_missing_user_secret(): void {
		// The default payload carries no `user_secret`.
		$payload = $this->buildPayload();

		$result = TokenManager::validate_token( $this->mintToken( $payload ), true );

		$this->assertWPError( $result );
		$this->assertSame( 'invalid-jwt', $result->get_error_code() );
	}

	/**
	 * Tests that a refresh token is rejected when the user secret is revoked.
	 */
	public function test_refresh_rejects_revoked_secret(): void {
		$user_secret = TokenManager::get_user_secret( $this->test_user, false );

		User::set_is_secret_revoked( $this->test_user, true );

		$payload = $this->buildPayload(
			[
				'data' => [
					'user' => [
						'user_secret' => $user_secret,
					],
				],
			]
		);

		$result = TokenManager::validate_token( $this->mintToken( $payload ), true );

		$this->assertWPError( $result );
		$this->assertSame( 'invalid-jwt', $result->get_error_code() );
	}

	/**
	 * Tests that a refresh token is rejected when the user secret does not match.
	 */
	public function test_refresh_rejects_mismatched_secret(): void {
		$payload = $this->buildPayload(
			[
				'data' => [
					'user' => [
						'user_secret' => 'not-the-stored-user-secret',
					],
				],
			]
		);

		$result = TokenManager::validate_token( $this->mintToken( $payload ), true );

		$this->assertWPError( $result );
		$this->assertSame( 'invalid-jwt', $result->get_error_code() );
	}

	/**
	 * Tests that a valid refresh token is accepted.
	 */
	public function test_refresh_accepts_valid_token(): void {
		$payload = $this->buildPayload(
			[
				'data' => [
					'user' => [
						'user_secret' => TokenManager::get_user_secret( $this->test_user, false ),
					],
				],
			]
		);

		$result = TokenManager::validate_token( $this->mintToken( $payload ), true );

		$this->assertNotWPError( $result );
		$this->assertIsObject( $result );
		$this->assertSame( $this->test_user, $result->data->user->id );
	}
}
