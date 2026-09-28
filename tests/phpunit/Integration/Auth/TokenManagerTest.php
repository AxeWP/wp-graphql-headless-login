<?php
/**
 * Tests TokenManager::validate_token().
 *
 * @package WPGraphQL\Login\Tests\Integration\Auth
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Tests\Integration\Auth;

use PHPUnit\Framework\Attributes\CoversClass;
use WPGraphQL\Login\Auth\TokenManager;
use WPGraphQL\Login\Auth\User;
use WPGraphQL\Login\Tests\TestCase;
use WPGraphQL\Login\Vendor\Firebase\JWT\JWT;

/**
 * Tests validating auth and refresh tokens with TokenManager::validate_token().
 */
#[CoversClass( TokenManager::class )]
class TokenManagerTest extends TestCase {
	/**
	 * The test user ID.
	 */
	private int $test_user;

	/**
	 * {@inheritDoc}
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->test_user = $this->factory()->user->create(
			[
				'role' => 'administrator',
			]
		);

		// Seeds the site secret key and the user secret.
		$this->generate_user_tokens( $this->test_user );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function tearDown(): void {
		User::set_is_secret_revoked( $this->test_user, false );
		wp_set_current_user( 0 );
		$this->reset_utils_properties();

		parent::tearDown();
	}

	/**
	 * Tests that a token issued by another site is rejected.
	 */
	public function test_rejects_wrong_issuer(): void {
		$payload = $this->build_payload(
			[
				'iss' => 'https://evil.example',
			]
		);

		$result = TokenManager::validate_token( $this->mint_token( $payload ) );

		$this->assertWPError( $result );
		$this->assertSame( 'invalid-jwt', $result->get_error_code() );
		$this->assertSame( 'The iss do not match with this server.', $result->get_error_message() );
	}

	/**
	 * Tests that an expired token is rejected.
	 */
	public function test_rejects_expired_token(): void {
		// Well beyond the 60s JWT::$leeway used by the validator.
		$payload = $this->build_payload(
			[
				'iat' => time() - ( 2 * HOUR_IN_SECONDS ),
				'nbf' => time() - ( 2 * HOUR_IN_SECONDS ),
				'exp' => time() - HOUR_IN_SECONDS,
			]
		);

		$result = TokenManager::validate_token( $this->mint_token( $payload ) );

		$this->assertWPError( $result );
		$this->assertSame( 'invalid-secret-key', $result->get_error_code() );
		$this->assertSame( 'Expired token', $result->get_error_message() );
	}

	/**
	 * Tests that a token signed with the wrong key is rejected.
	 */
	public function test_rejects_tampered_signature(): void {
		$payload = $this->build_payload();

		// The JWT library requires a minimum HMAC key length, so pad the wrong key.
		$wrong_key = str_repeat( 'not-the-real-key', 4 );

		$result = TokenManager::validate_token( $this->mint_token( $payload, $wrong_key ) );

		$this->assertWPError( $result );
		$this->assertSame( 'invalid-secret-key', $result->get_error_code() );
		$this->assertSame( 'Signature verification failed', $result->get_error_message() );
	}

	/**
	 * Tests that a refresh token is rejected when validated as an auth token.
	 */
	public function test_rejects_refresh_token_used_as_auth_token(): void {
		$payload = $this->build_payload(
			[
				'data' => [
					'user' => [
						'user_secret' => TokenManager::get_user_secret( $this->test_user, false ),
					],
				],
			]
		);

		// Validated as an auth token, i.e. `$is_refresh_token = false`.
		$result = TokenManager::validate_token( $this->mint_token( $payload ), false );

		$this->assertWPError( $result );
		$this->assertSame( 'invalid-jwt', $result->get_error_code() );
		$this->assertSame( 'Refresh token cannot be used as an auth token.', $result->get_error_message() );
	}

	/**
	 * Tests that a token without a user ID is rejected.
	 */
	public function test_rejects_missing_user_id(): void {
		$payload = $this->build_payload(
			[
				'data' => [
					'user' => [
						'id' => null,
					],
				],
			]
		);

		$result = TokenManager::validate_token( $this->mint_token( $payload ) );

		$this->assertWPError( $result );
		$this->assertSame( 'invalid-jwt', $result->get_error_code() );
		$this->assertSame( 'User ID not found in the token.', $result->get_error_message() );
	}

	/**
	 * Tests that a refresh token without a user secret is rejected.
	 */
	public function test_refresh_rejects_missing_user_secret(): void {
		// The default payload carries no `user_secret`.
		$payload = $this->build_payload();

		$result = TokenManager::validate_token( $this->mint_token( $payload ), true );

		$this->assertWPError( $result );
		$this->assertSame( 'invalid-jwt', $result->get_error_code() );
		$this->assertSame( 'User secret not found in the token.', $result->get_error_message() );
	}

	/**
	 * Tests that a refresh token is rejected once the user secret is revoked.
	 */
	public function test_refresh_rejects_revoked_secret(): void {
		$user_secret = TokenManager::get_user_secret( $this->test_user, false );

		User::set_is_secret_revoked( $this->test_user, true );

		$payload = $this->build_payload(
			[
				'data' => [
					'user' => [
						'user_secret' => $user_secret,
					],
				],
			]
		);

		$result = TokenManager::validate_token( $this->mint_token( $payload ), true );

		$this->assertWPError( $result );
		$this->assertSame( 'invalid-jwt', $result->get_error_code() );
		$this->assertSame( 'User secret is revoked.', $result->get_error_message() );
	}

	/**
	 * Tests that a refresh token with a user secret that doesn't match the stored one is rejected.
	 */
	public function test_refresh_rejects_mismatched_secret(): void {
		$payload = $this->build_payload(
			[
				'data' => [
					'user' => [
						'user_secret' => 'not-the-stored-user-secret',
					],
				],
			]
		);

		$result = TokenManager::validate_token( $this->mint_token( $payload ), true );

		$this->assertWPError( $result );
		$this->assertSame( 'invalid-jwt', $result->get_error_code() );
		$this->assertSame( 'User secret does not match.', $result->get_error_message() );
	}

	/**
	 * Tests that a valid refresh token is decoded with its user ID.
	 */
	public function test_refresh_accepts_valid_token(): void {
		$payload = $this->build_payload(
			[
				'data' => [
					'user' => [
						'user_secret' => TokenManager::get_user_secret( $this->test_user, false ),
					],
				],
			]
		);

		$result = TokenManager::validate_token( $this->mint_token( $payload ), true );

		$this->assertNotWPError( $result );
		$this->assertSame( $this->test_user, $result->data->user->id );
	}

	/**
	 * Builds a token payload with valid defaults, merged with the given overrides.
	 *
	 * @param array<string,mixed> $overrides The payload values to replace, merged recursively.
	 *
	 * @return array<string,mixed>
	 */
	private function build_payload( array $overrides = [] ): array {
		$payload = [
			'iss'  => home_url(),
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
	 *
	 * @param array<string,mixed> $payload The token payload.
	 * @param ?string             $key     The signing key. Defaults to the site secret key.
	 */
	private function mint_token( array $payload, ?string $key = null ): string {
		JWT::$leeway = 60;

		return JWT::encode( $payload, $key ?? TokenManager::get_secret_key(), 'HS256' );
	}
}
