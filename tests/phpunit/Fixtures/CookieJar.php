<?php
/**
 * Holds the cookies recorded by the `WPGraphQL\Login\Auth\setcookie()` fixture.
 *
 * @package Tests\WPGraphQL\Login\Fixtures
 */

declare( strict_types = 1 );

namespace Tests\WPGraphQL\Login\Fixtures;

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
