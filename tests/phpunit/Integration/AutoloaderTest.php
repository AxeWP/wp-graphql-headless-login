<?php
/**
 * Tests the Autoloader.
 *
 * @package WPGraphQL\Login\Tests\Integration
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Tests\Integration;

use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionMethod;
use WPGraphQL\Login\Autoloader;
use WPGraphQL\Login\Tests\TestCase;

/**
 * Tests the Autoloader class.
 */
#[CoversClass( Autoloader::class )]
class AutoloaderTest extends TestCase {
	/**
	 * Tests that require_autoloader() loads a readable file, and flags a missing one with an admin notice.
	 */
	public function test_require_autoloader_loads_file_or_shows_missing_notice(): void {
		$method = new ReflectionMethod( Autoloader::class, 'require_autoloader' );

		$this->assertTrue( $method->invoke( null, WPGRAPHQL_LOGIN_PLUGIN_DIR . 'vendor/autoload.php' ) );
		$this->assertFalse( $method->invoke( null, '/path/to/invalid/autoload.php' ) );

		// The missing autoloader notice is displayed and flagged as incorrect usage.
		$this->setExpectedIncorrectUsage( Autoloader::class );
		$this->expectOutputRegex( '/The Composer autoloader was not found/' );

		do_action( 'admin_notices' );
	}
}
