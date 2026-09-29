<?php
/**
 * Tests the TokenManager class.
 *
 * @package WPGraphQL\Login\Tests\Integration\Auth
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Tests\Integration\Auth;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use WPGraphQL\Login\Auth\TokenManager;
use WPGraphQL\Login\Auth\User;
use WPGraphQL\Login\Settings\PluginSettings;
use WPGraphQL\Login\Tests\TestCase;
use WPGraphQL\Login\Vendor\Firebase\JWT\JWT;

/**
 * Tests issuing and validating auth and refresh tokens, and managing the secrets used to sign them.
 */
#[CoversClass( TokenManager::class )]
#[CoversClass( User::class )]
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
		unset( $_SERVER['HTTP_AUTHORIZATION'], $_SERVER['REDIRECT_HTTP_AUTHORIZATION'], $_SERVER['HTTP_REFRESH_AUTHORIZATION'] );
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
	 * Tests that the auth token is read from the Authorization header when none is provided.
	 */
	public function test_validates_token_from_authorization_header(): void {
		$token = $this->mint_token( $this->build_payload() );

		// No header.
		$this->assertNull( TokenManager::validate_token() );

		// Not a bearer token.
		$_SERVER['HTTP_AUTHORIZATION'] = 'Basic ' . $token;
		$this->assertNull( TokenManager::validate_token() );

		// A bearer scheme without a token.
		$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer';
		$this->assertNull( TokenManager::validate_token() );

		// The scheme is case-insensitive.
		$_SERVER['HTTP_AUTHORIZATION'] = 'bearer ' . $token;
		$this->assertSame( $this->test_user, TokenManager::validate_token()->data->user->id );

		$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
		$this->assertSame( $this->test_user, TokenManager::validate_token()->data->user->id );

		// Some servers only pass the redirected header.
		unset( $_SERVER['HTTP_AUTHORIZATION'] );
		$_SERVER['REDIRECT_HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
		$this->assertSame( $this->test_user, TokenManager::validate_token()->data->user->id );

		// The header can be filtered.
		add_filter( 'graphql_login_auth_header', '__return_empty_string' );
		$this->assertNull( TokenManager::validate_token() );
	}

	/**
	 * Tests that the refresh token header is read from the request, and can be filtered.
	 */
	public function test_get_refresh_header(): void {
		$this->assertSame( '', TokenManager::get_refresh_header() );

		$_SERVER['HTTP_REFRESH_AUTHORIZATION'] = 'my-refresh-token';
		$this->assertSame( 'my-refresh-token', TokenManager::get_refresh_header() );

		add_filter( 'graphql_login_refresh_header', static fn (): string => 'filtered-refresh-token' );
		$this->assertSame( 'filtered-refresh-token', TokenManager::get_refresh_header() );
	}

	/**
	 * Tests that a site secret is generated and stored if none exists, and that it can be filtered.
	 */
	public function test_get_secret_key_generates_missing_secret(): void {
		update_option( PluginSettings::get_slug(), [] );
		$this->reset_utils_properties();

		$secret = TokenManager::get_secret_key();

		$this->assertSame( 64, strlen( $secret ) );

		$this->reset_utils_properties();
		$this->assertSame( $secret, TokenManager::get_secret_key(), 'The generated secret should be stored.' );

		$filtered_secret = str_repeat( 'filtered-secret-', 4 );
		add_filter( 'graphql_login_jwt_secret_key', static fn (): string => $filtered_secret );
		$this->assertSame( $filtered_secret, TokenManager::get_secret_key() );
	}

	/**
	 * Tests that a secret key too short to sign tokens with is treated as missing, instead of causing a fatal error.
	 */
	public function test_short_secret_key_is_rejected(): void {
		wp_set_current_user( $this->test_user );
		$token = $this->mint_token( $this->build_payload() );

		add_filter( 'graphql_login_jwt_secret_key', static fn (): string => 'too-short' );

		$this->assertSame( '', TokenManager::get_secret_key() );
		$this->assertNull( TokenManager::get_auth_token( wp_get_current_user() ) );

		$actual = TokenManager::validate_token( $token );

		$this->assertWPError( $actual );
		$this->assertSame( 'invalid-secret-key', $actual->get_error_code() );
	}

	/**
	 * Tests that each token is timed from when it's issued, using the current validity.
	 */
	public function test_tokens_are_timed_when_issued(): void {
		wp_set_current_user( $this->test_user );
		$user = wp_get_current_user();

		$token = TokenManager::validate_token( TokenManager::get_auth_token( $user ) );
		$this->assertSame( 300, $token->exp - $token->iat );

		add_filter( 'graphql_login_token_validity', static fn (): int => 60 );
		add_filter( 'graphql_login_refresh_token_validity', static fn (): int => DAY_IN_SECONDS );

		$token = TokenManager::validate_token( TokenManager::get_auth_token( $user ) );
		$this->assertSame( 60, $token->exp - $token->iat );
		$this->assertEqualsWithDelta( time(), $token->iat, 5 );

		$refresh_token = TokenManager::validate_token( TokenManager::get_refresh_token( $user ), true );
		$this->assertSame( DAY_IN_SECONDS, $refresh_token->exp - $refresh_token->iat );
	}

	/**
	 * Tests that the secret key defined with the documented constant is used to sign tokens.
	 */
	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_get_secret_key_uses_constant(): void {
		$secret = str_repeat( 'constant-secret-', 4 );
		define( 'WPGRAPHQL_LOGIN_JWT_SECRET_KEY', $secret );

		$this->assertSame( $secret, TokenManager::get_secret_key() );

		// Tokens signed with the stored secret are rejected.
		$this->assertWPError( TokenManager::validate_token( $this->mint_token( $this->build_payload(), (string) graphql_login_get_setting( 'jwt_secret_key' ) ) ) );
		$this->assertSame( $this->test_user, TokenManager::validate_token( $this->mint_token( $this->build_payload(), $secret ) )->data->user->id );
	}

	/**
	 * Tests that the legacy constant is still supported.
	 */
	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_get_secret_key_supports_legacy_constant(): void {
		$secret = str_repeat( 'legacy-secret-', 5 );
		define( 'GRAPHQL_LOGIN_JWT_SECRET_KEY', $secret );
		$this->setExpectedDeprecated( 'GRAPHQL_LOGIN_JWT_SECRET_KEY' );
		$this->assertSame( $secret, TokenManager::get_secret_key() );
	}

	/**
	 * Tests that tokens and secrets are only issued to the current user when enforced.
	 */
	public function test_tokens_are_only_issued_to_the_current_user(): void {
		$user = get_user_by( 'id', $this->test_user );

		wp_set_current_user( $this->factory()->user->create( [ 'role' => 'administrator' ] ) );

		$this->assertNull( TokenManager::get_auth_token( $user ) );
		$this->assertNull( TokenManager::get_refresh_token( $user ) );
		$this->assertNull( TokenManager::get_user_secret( $this->test_user ) );

		$actual = TokenManager::issue_new_user_secret( $this->test_user );
		$this->assertWPError( $actual );
		$this->assertSame( 'graphql-headless-login-no-permissions', $actual->get_error_code() );

		// The current user can get their own tokens.
		wp_set_current_user( $this->test_user );

		$this->assertSame( $this->test_user, TokenManager::validate_token( TokenManager::get_auth_token( $user ) )->data->user->id );
		$this->assertSame( $this->test_user, TokenManager::validate_token( TokenManager::get_refresh_token( $user ), true )->data->user->id );
	}

	/**
	 * Tests that a user secret is issued if the user doesn't have one, and that no refresh token is issued if the secret is unavailable.
	 */
	public function test_user_secret_is_issued_when_missing(): void {
		wp_set_current_user( $this->test_user );
		delete_user_meta( $this->test_user, 'graphql_login_secret' );

		$secret = TokenManager::get_user_secret( $this->test_user );

		$this->assertNotEmpty( $secret );
		$this->assertSame( $secret, User::get_secret( $this->test_user ) );

		add_filter( 'graphql_login_user_secret', static fn () => new \WP_Error( 'no-secret', 'No secret.' ) );

		$this->assertNull( TokenManager::get_user_secret( $this->test_user ) );
		$this->assertNull( TokenManager::get_refresh_token( get_user_by( 'id', $this->test_user ) ) );
	}

	/**
	 * Tests that users can revoke their own secret, and users with the auth capability can revoke others' when not enforcing the current user.
	 */
	public function test_revoke_user_secret(): void {
		$subscriber = $this->factory()->user->create( [ 'role' => 'subscriber' ] );
		$admin      = $this->factory()->user->create( [ 'role' => 'administrator' ] );

		// Other users can't revoke the secret.
		wp_set_current_user( $subscriber );

		$actual = TokenManager::revoke_user_secret( $this->test_user, false );
		$this->assertWPError( $actual );
		$this->assertSame( 'graphql-headless-login-cannot-revoke-secret', $actual->get_error_code() );

		wp_set_current_user( $admin );

		$actual = TokenManager::revoke_user_secret( $this->test_user );
		$this->assertWPError( $actual );
		$this->assertFalse( TokenManager::is_user_secret_revoked( $this->test_user ) );

		// Unless they have the auth capability, and the current user isn't enforced.
		$this->assertTrue( TokenManager::revoke_user_secret( $this->test_user, false ) );
		$this->assertTrue( TokenManager::is_user_secret_revoked( $this->test_user ) );
		$this->assertNull( User::get_secret( $this->test_user ) );

		// Users can revoke their own secret.
		User::set_is_secret_revoked( $this->test_user, false );
		wp_set_current_user( $this->test_user );

		$this->assertTrue( TokenManager::revoke_user_secret( $this->test_user ) );
		$this->assertTrue( TokenManager::is_user_secret_revoked( $this->test_user ) );
	}

	/**
	 * Tests that refreshing a user secret replaces it and restores a revoked one.
	 */
	public function test_refresh_user_secret(): void {
		$old_secret = User::get_secret( $this->test_user );
		User::set_is_secret_revoked( $this->test_user, true );

		// Other users can't refresh the secret.
		wp_set_current_user( $this->factory()->user->create( [ 'role' => 'subscriber' ] ) );

		$actual = TokenManager::refresh_user_secret( $this->test_user );
		$this->assertWPError( $actual );
		$this->assertSame( 'graphql-headless-login-cannot-refresh-secret', $actual->get_error_code() );
		$this->assertTrue( TokenManager::is_user_secret_revoked( $this->test_user ) );

		wp_set_current_user( $this->test_user );

		$this->assertTrue( TokenManager::refresh_user_secret( $this->test_user ) );
		$this->assertFalse( TokenManager::is_user_secret_revoked( $this->test_user ) );

		$new_secret = User::get_secret( $this->test_user );
		$this->assertNotEmpty( $new_secret );
		$this->assertNotSame( $old_secret, $new_secret );
	}

	/**
	 * Tests that the capability needed to manage other users' secrets can be filtered.
	 */
	public function test_auth_capability_can_be_filtered(): void {
		wp_set_current_user( $this->factory()->user->create( [ 'role' => 'editor' ] ) );

		$this->assertFalse( TokenManager::current_user_can( $this->test_user, false ) );

		add_filter( 'graphql_login_edit_jwt_capability', static fn (): string => 'edit_others_posts' );

		$this->assertTrue( TokenManager::current_user_can( $this->test_user, false ) );
		$this->assertFalse( TokenManager::current_user_can( $this->test_user ), 'The current user should still be enforced.' );
		$this->assertTrue( TokenManager::current_user_can( $this->test_user, false, false ), 'Nothing should be enforced.' );
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
