<?php
/**
 * Tests authenticating the current WordPress user from the Authorization header.
 *
 * @package Tests\WPGraphQL\Login\Integration\Auth
 */

namespace Tests\WPGraphQL\Login\Integration\Auth;

use PHPUnit\Framework\Attributes\CoversClass;
use Tests\WPGraphQL\Login\TestCase;
use WPGraphQL\Login\Auth\ServerAuthentication;
use WPGraphQL\Login\Utils\Utils;

/**
 * Tests ServerAuthentication.
 */
#[CoversClass( ServerAuthentication::class )]
class ServerAuthenticationTest extends TestCase {
	/**
	 * The ID of the user the tokens are issued for.
	 *
	 * @var int
	 */
	public $user_id;

	/**
	 * The ID of a user authenticated by another method earlier in the `determine_current_user` chain.
	 *
	 * @var int
	 */
	public $admin;

	/**
	 * {@inheritDoc}
	 */
	public function setUp(): void {
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
	public function tearDown(): void {
		unset( $_SERVER['HTTP_AUTHORIZATION'] );
		$this->reset_utils_properties();
		wp_set_current_user( 0 );

		parent::tearDown();
	}

	public function testValidAuthTokenAuthenticatesCurrentUser(): void {
		$tokens = $this->generate_user_tokens( $this->user_id );

		$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $tokens['auth_token'];

		$this->assertSame( $this->user_id, $this->determine_current_user_id() );
	}

	public function testValidAuthTokenTakesPrecedenceOverPreviouslyDeterminedUser(): void {
		$tokens = $this->generate_user_tokens( $this->user_id );

		$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $tokens['auth_token'];

		$this->authenticate_admin_by_other_method();

		$this->assertSame( $this->user_id, $this->determine_current_user_id() );
	}

	public function testWithoutAuthHeaderUserIsUnchanged(): void {
		$this->assertSame( 0, $this->determine_current_user_id() );

		$this->authenticate_admin_by_other_method();

		$this->assertSame( $this->admin, $this->determine_current_user_id() );
	}

	public function testMalformedTokenDoesNotAuthenticate(): void {
		$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer not-a-valid-jwt';

		$this->assertSame( 0, $this->determine_current_user_id() );

		$this->authenticate_admin_by_other_method();

		$this->assertSame( $this->admin, $this->determine_current_user_id() );
	}

	public function testRefreshTokenCannotBeUsedToAuthenticate(): void {
		$tokens = $this->generate_user_tokens( $this->user_id );

		$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $tokens['refresh_token'];

		$this->assertSame( 0, $this->determine_current_user_id() );

		$this->authenticate_admin_by_other_method();

		$this->assertSame( $this->admin, $this->determine_current_user_id() );
	}

	public function testTokenSignedWithPreviousSiteSecretDoesNotAuthenticate(): void {
		$tokens = $this->generate_user_tokens( $this->user_id );

		// Rotate the site secret, invalidating all previously-issued tokens.
		Utils::update_plugin_setting( 'jwt_secret_key', wp_generate_password( 64, false, false ) );

		$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $tokens['auth_token'];

		$this->assertSame( 0, $this->determine_current_user_id() );
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
