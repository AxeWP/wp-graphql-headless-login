<?php
/**
 * Tests the authentication cookies issued by AuthCookie.
 *
 * @package Tests\WPGraphQL\Login\Integration\Auth
 */

declare( strict_types = 1 );

namespace Tests\WPGraphQL\Login\Integration\Auth;

use Tests\WPGraphQL\Login\TestCase;
use WPGraphQL\Login\Auth\AuthCookie;

/**
 * Tests `AuthCookie::set_auth_cookie()`.
 *
 * The `SameSite` and `Domain` cookie attributes are covered by PasswordLoginTest.
 */
class AuthCookieTest extends TestCase {
	/**
	 * The test user ID.
	 *
	 * @var int
	 */
	private $user_id;

	/**
	 * {@inheritDoc}
	 */
	public function setUp(): void {
		parent::setUp();

		$this->user_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
	}

	/**
	 * Tests that the core cookie hooks fire with a valid, parseable cookie for the user.
	 */
	public function test_fires_hooks_with_valid_cookies(): void {
		$events = $this->capture_auth_cookie_events( fn () => AuthCookie::set_auth_cookie( $this->user_id ) )['events'];

		$this->assertCount( 1, $events['set_auth_cookie'] );
		$this->assertCount( 1, $events['set_logged_in_cookie'] );

		[ $cookie, $expire, $expiration, $user_id, $scheme, $token ] = $events['set_logged_in_cookie'][0];

		$this->assertSame( 0, $expire, 'Without `$remember` this should be a session cookie.' );
		$this->assertGreaterThan( time(), $expiration );
		$this->assertSame( $this->user_id, $user_id );
		$this->assertSame( 'logged_in', $scheme );

		$parsed = wp_parse_auth_cookie( $cookie, 'logged_in' );

		$this->assertNotFalse( $parsed );
		$this->assertSame( get_userdata( $this->user_id )->user_login, $parsed['username'] );
		$this->assertSame( $token, $parsed['token'] );
	}

	/**
	 * Tests that `$remember` extends the cookie past the session and lengthens expiration.
	 */
	public function test_remember_sets_a_persistent_cookie(): void {
		$events = $this->capture_auth_cookie_events( fn () => AuthCookie::set_auth_cookie( $this->user_id, true ) )['events'];

		[ , $expire, $expiration ] = $events['set_logged_in_cookie'][0];

		$this->assertSame( $expiration + ( 12 * HOUR_IN_SECONDS ), $expire );
		$this->assertGreaterThan( time() + ( 13 * DAY_IN_SECONDS ), $expiration );
	}
}
