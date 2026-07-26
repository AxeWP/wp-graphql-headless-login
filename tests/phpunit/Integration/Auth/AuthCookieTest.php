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
 * NOT COVERED: the `SameSite` and `Domain` attributes, which are the reason this class
 * exists. They are only applied in the `setcookie()` call, and wp-phpunit's bootstrap
 * emits output before any test runs, so `headers_sent()` is already true and every
 * `setcookie()` fails. Nothing observable in-process reflects those two settings.
 * Covering them needs either a seam in `AuthCookie` or a browser-level test.
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
		$events = $this->capture_auth_cookie_events();

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
	 * Tests that the session token is registered against the user, so the cookie validates.
	 */
	public function test_creates_a_verifiable_session_token(): void {
		$events = $this->capture_auth_cookie_events();

		$token   = $events['set_logged_in_cookie'][0][5];
		$manager = \WP_Session_Tokens::get_instance( $this->user_id );

		$this->assertTrue( $manager->verify( $token ) );
	}

	/**
	 * Tests that `$remember` extends the cookie past the session and lengthens expiration.
	 */
	public function test_remember_sets_a_persistent_cookie(): void {
		$events = $this->capture_auth_cookie_events( true );

		[ , $expire, $expiration ] = $events['set_logged_in_cookie'][0];

		$this->assertSame( $expiration + ( 12 * HOUR_IN_SECONDS ), $expire );
		$this->assertGreaterThan( time() + ( 13 * DAY_IN_SECONDS ), $expiration );
	}

	/**
	 * Tests that the `auth_cookie_expiration` filter is honored.
	 */
	public function test_expiration_is_filterable(): void {
		$filter = static fn (): int => 60;

		add_filter( 'auth_cookie_expiration', $filter );
		$events = $this->capture_auth_cookie_events();
		remove_filter( 'auth_cookie_expiration', $filter );

		$this->assertEqualsWithDelta( time() + 60, $events['set_logged_in_cookie'][0][2], 5 );
	}

	/**
	 * Sets the auth cookies, returning the core cookie events they fired.
	 *
	 * @param bool $remember Whether to remember the user.
	 *
	 * @return array{set_auth_cookie:list<array<int,mixed>>,set_logged_in_cookie:list<array<int,mixed>>}
	 */
	private function capture_auth_cookie_events( bool $remember = false ): array {
		$events = [
			'set_auth_cookie'      => [],
			'set_logged_in_cookie' => [],
		];

		$auth_hook      = static function ( ...$args ) use ( &$events ): void {
			$events['set_auth_cookie'][] = $args;
		};
		$logged_in_hook = static function ( ...$args ) use ( &$events ): void {
			$events['set_logged_in_cookie'][] = $args;
		};

		call_user_func( 'add_action', 'set_auth_cookie', $auth_hook, 10, 6 );
		call_user_func( 'add_action', 'set_logged_in_cookie', $logged_in_hook, 10, 6 );

		try {
			// wp-phpunit filters `send_auth_cookies` to false suite-wide, so this returns
			// before `setcookie()`. Both hooks above fire ahead of that gate.
			AuthCookie::set_auth_cookie( $this->user_id, $remember );
		} finally {
			call_user_func( 'remove_action', 'set_auth_cookie', $auth_hook, 10 );
			call_user_func( 'remove_action', 'set_logged_in_cookie', $logged_in_hook, 10 );
		}

		return $events;
	}
}
