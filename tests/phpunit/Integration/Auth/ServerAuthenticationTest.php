<?php
/**
 * Tests authenticating the current WordPress user from the Authorization header.
 *
 * @package WPGraphQL\Login\Tests\Integration\Auth
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Tests\Integration\Auth;

use PHPUnit\Framework\Attributes\CoversClass;
use WPGraphQL\Login\Auth\ServerAuthentication;
use WPGraphQL\Login\Tests\TestCase;
use WPGraphQL\Login\Utils\Utils;

/**
 * Tests authenticating the current user from the auth token in the `Authorization` header.
 */
#[CoversClass( ServerAuthentication::class )]
class ServerAuthenticationTest extends TestCase {
	/**
	 * The ID of the user the tokens are issued for.
	 */
	private int $user_id;

	/**
	 * The ID of a user authenticated by another method earlier in the `determine_current_user` chain.
	 */
	private int $admin;

	/**
	 * {@inheritDoc}
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->user_id = $this->factory()->user->create();
		$this->admin   = $this->factory()->user->create(
			[
				'role' => 'administrator',
			]
		);
	}

	/**
	 * {@inheritDoc}
	 */
	protected function tearDown(): void {
		unset( $_SERVER['HTTP_AUTHORIZATION'] );
		$this->reset_utils_properties();
		wp_set_current_user( 0 );

		parent::tearDown();
	}

	/**
	 * Tests that a valid auth token authenticates its user.
	 */
	public function test_valid_auth_token_authenticates_current_user(): void {
		$tokens = $this->generate_user_tokens( $this->user_id );

		$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $tokens['auth_token'];

		$this->assertSame( $this->user_id, $this->determine_current_user_id() );
	}

	/**
	 * Tests that a valid auth token overrides a user determined by another authentication method.
	 */
	public function test_valid_auth_token_takes_precedence_over_previously_determined_user(): void {
		$tokens = $this->generate_user_tokens( $this->user_id );

		$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $tokens['auth_token'];

		$this->authenticate_admin_by_other_method();

		$this->assertSame( $this->user_id, $this->determine_current_user_id() );
	}

	/**
	 * Tests that without an `Authorization` header the current user is left unchanged.
	 */
	public function test_without_auth_header_user_is_unchanged(): void {
		$this->assertSame( 0, $this->determine_current_user_id() );

		$this->authenticate_admin_by_other_method();

		$this->assertSame( $this->admin, $this->determine_current_user_id() );
	}

	/**
	 * Tests that a malformed token neither authenticates nor overrides the previously determined user.
	 */
	public function test_malformed_token_does_not_authenticate(): void {
		$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer not-a-valid-jwt';

		$this->assertSame( 0, $this->determine_current_user_id() );

		$this->authenticate_admin_by_other_method();

		$this->assertSame( $this->admin, $this->determine_current_user_id() );
	}

	/**
	 * Tests that a refresh token neither authenticates nor overrides the previously determined user.
	 */
	public function test_refresh_token_cannot_be_used_to_authenticate(): void {
		$tokens = $this->generate_user_tokens( $this->user_id );

		$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $tokens['refresh_token'];

		$this->assertSame( 0, $this->determine_current_user_id() );

		$this->authenticate_admin_by_other_method();

		$this->assertSame( $this->admin, $this->determine_current_user_id() );
	}

	/**
	 * Tests that rotating the site secret invalidates previously issued auth tokens.
	 */
	public function test_token_signed_with_previous_site_secret_does_not_authenticate(): void {
		$tokens = $this->generate_user_tokens( $this->user_id );

		// Rotate the site secret, invalidating all previously-issued tokens.
		Utils::update_plugin_setting( 'jwt_secret_key', wp_generate_password( 64, false, false ) );

		$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $tokens['auth_token'];

		$this->assertSame( 0, $this->determine_current_user_id() );
	}

	/**
	 * Tests that determining the current user while validating the token doesn't recurse.
	 */
	public function test_nested_current_user_lookup_does_not_recurse(): void {
		$tokens = $this->generate_user_tokens( $this->user_id );

		$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $tokens['auth_token'];

		$nested_user_id = null;
		add_filter(
			'graphql_login_auth_header',
			static function ( string $header ) use ( &$nested_user_id ): string {
				// E.g. a capability check while filtering the header.
				$nested_user_id = apply_filters( 'determine_current_user', false );

				return $header;
			}
		);

		$this->assertSame( $this->user_id, $this->determine_current_user_id() );
		$this->assertFalse( $nested_user_id, 'The nested lookup should skip token authentication.' );
	}

	/**
	 * Simulates another authentication method (e.g. an auth cookie) determining the current user before ours runs.
	 */
	private function authenticate_admin_by_other_method(): void {
		add_filter( 'determine_current_user', fn () => $this->admin, 50 );
	}

	/**
	 * Clears the cached current user and lets WordPress determine it from the request.
	 */
	private function determine_current_user_id(): int {
		$GLOBALS['current_user'] = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Forces wp_get_current_user() to re-run `determine_current_user`.

		return wp_get_current_user()->ID;
	}
}
