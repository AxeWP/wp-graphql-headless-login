<?php
/**
 * Authentication Cookie.
 *
 * @package WPGraphQL\Login\Auth
 * @since @todo
 */

declare(strict_types=1);

namespace WPGraphQL\Login\Auth;

use WPGraphQL\Login\Utils\Utils;

/**
 * Class AuthCookie
 *
 * Handles custom authentication cookies, including setting the SameSite attribute.
 */
class AuthCookie {
	/**
	 * Sets the authentication cookies based on user ID.
	 * Provides an alternative to `wp_set_auth_cookie` that supports the SameSite cookie attribute.
	 *
	 * @param int  $user_id  User ID.
	 * @param bool $remember Whether to remember the user.
	 */
	public static function set_auth_cookie( int $user_id, bool $remember = false ): void {
		$expiration = self::get_auth_cookie_expiration( $user_id, $remember );
		$expire     = $remember ? $expiration + ( 12 * HOUR_IN_SECONDS ) : 0;

		$secure                  = is_ssl();
		$secure_logged_in_cookie = $secure && 'https' === wp_parse_url( get_option( 'home' ), PHP_URL_SCHEME );

		$secure                  = apply_filters( 'secure_auth_cookie', $secure, $user_id ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		$secure_logged_in_cookie = apply_filters( 'secure_logged_in_cookie', $secure_logged_in_cookie, $user_id, $secure ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

		$auth_cookie_name = $secure ? SECURE_AUTH_COOKIE : AUTH_COOKIE;
		$scheme           = $secure ? 'secure_auth' : 'auth';

		$manager = \WP_Session_Tokens::get_instance( $user_id );
		$token   = $manager->create( $expiration );

		$auth_cookie      = wp_generate_auth_cookie( $user_id, $expiration, $scheme, $token );
		$logged_in_cookie = wp_generate_auth_cookie( $user_id, $expiration, 'logged_in', $token );

		do_action( 'set_auth_cookie', $auth_cookie, $expire, $expiration, $user_id, $scheme, $token ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		do_action( 'set_logged_in_cookie', $logged_in_cookie, $expire, $expiration, $user_id, 'logged_in', $token ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

		if ( ! apply_filters( 'send_auth_cookies', true ) ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			return;
		}

		/** @var 'None'|'Lax'|'Strict' $samesite */
		$samesite      = Utils::get_cookie_setting( 'sameSiteOption', 'Lax' );
		$cookie_domain = Utils::get_cookie_setting( 'cookieDomain', '' );

		self::set_custom_cookie(
			$auth_cookie_name,
			$auth_cookie,
			[
				'expires'  => $expire,
				'path'     => PLUGINS_COOKIE_PATH,
				'domain'   => $cookie_domain,
				'samesite' => $samesite,
				'secure'   => $secure,
			]
		);
		self::set_custom_cookie(
			$auth_cookie_name,
			$auth_cookie,
			[
				'expires'  => $expire,
				'path'     => ADMIN_COOKIE_PATH,
				'domain'   => $cookie_domain,
				'samesite' => $samesite,
				'secure'   => $secure,
			]
		);
		self::set_custom_cookie(
			LOGGED_IN_COOKIE,
			$logged_in_cookie,
			[
				'expires'  => $expire,
				'path'     => COOKIEPATH,
				'domain'   => $cookie_domain,
				'samesite' => $samesite,
				'secure'   => $secure_logged_in_cookie,
			]
		);

		if ( COOKIEPATH !== SITECOOKIEPATH ) {
			self::set_custom_cookie(
				LOGGED_IN_COOKIE,
				$logged_in_cookie,
				[
					'expires'  => $expire,
					'path'     => SITECOOKIEPATH,
					'domain'   => $cookie_domain,
					'samesite' => $samesite,
					'secure'   => $secure_logged_in_cookie,
				]
			);
		}
	}

	/**
	 * Get the expiration time for the authentication cookie.
	 *
	 * @param int  $user_id  The ID of the user.
	 * @param bool $remember Whether to remember the user.
	 *
	 * @return int The expiration time in seconds.
	 */
	private static function get_auth_cookie_expiration( int $user_id, bool $remember ): int {
		$default_expiration = ( $remember ? 14 : 2 ) * DAY_IN_SECONDS;
		return time() + apply_filters( 'auth_cookie_expiration', $default_expiration, $user_id, $remember ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
	}

	/**
	 * Wrapper for `set_custom_cookie` that includes SameSite attribute.
	 *
	 * @param string                                                                                  $name     The name of the cookie.
	 * @param string                                                                                  $value    The value of the cookie.
	 * @param array{expires:int,path:string,domain:string,samesite:'Lax'|'None'|'Strict',secure:bool} $options The cookie options.
	 */
	private static function set_custom_cookie( string $name, string $value, array $options ): void {
		// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.cookies_setcookie
		setcookie(
			$name,
			$value,
			$options
		);
	}
}
