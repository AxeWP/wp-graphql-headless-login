<?php
/**
 * Records the cookies set by `AuthCookie` instead of sending them.
 *
 * `AuthCookie` calls `setcookie()` unqualified from the `WPGraphQL\Login\Auth` namespace,
 * so PHP resolves this function before the global one. Cookies can't be sent from the CLI,
 * so the tests assert on the recorded ones instead.
 *
 * @package WPGraphQL\Login\Tests\Fixtures
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Auth;

use WPGraphQL\Login\Tests\Fixtures\CookieJar;

/**
 * Records the cookie in the CookieJar.
 *
 * @param string              $name    The cookie name.
 * @param string              $value   The cookie value.
 * @param array<string,mixed> $options The cookie options.
 */
function setcookie( string $name, string $value = '', array $options = [] ): bool {
	CookieJar::$cookies[] = [
		'name'    => $name,
		'value'   => $value,
		'options' => $options,
	];

	return true;
}
