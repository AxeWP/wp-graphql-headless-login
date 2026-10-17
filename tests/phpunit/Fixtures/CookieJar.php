<?php
/**
 * Holds the cookies recorded by the `WPGraphQL\Login\Auth\setcookie()` fixture.
 *
 * @package WPGraphQL\Login\Tests\Fixtures
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Tests\Fixtures;

/**
 * Class - CookieJar
 */
final class CookieJar {
	/**
	 * The recorded cookies, in the order they were set.
	 *
	 * @var list<array{name:string,value:string,options:array<string,mixed>}>
	 */
	public static array $cookies = [];
}
