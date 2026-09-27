<?php
/**
 * Tests the Autoloader.
 *
 * @package Tests\WPGraphQL\Login\Integration
 */

namespace Tests\WPGraphQL\Login\Integration;

use Tests\WPGraphQL\Login\TestCase;
use WPGraphQL\Login\Autoloader;

/**
 * Tests Autoloader.
 */
class AutoloaderTest extends TestCase {
	/**
	 * Tests `Autoloader::require_autoloader()` with a valid and an invalid path.
	 */
	public function testRequireAutoloader() {
		$method = new \ReflectionMethod( Autoloader::class, 'require_autoloader' );

		$this->assertTrue( $method->invoke( null, WPGRAPHQL_LOGIN_PLUGIN_DIR . 'vendor/autoload.php' ) );
		$this->assertFalse( $method->invoke( null, '/path/to/invalid/autoload.php' ) );

		// The missing autoloader notice is displayed and flagged as incorrect usage.
		$this->setExpectedIncorrectUsage( Autoloader::class );
		$this->expectOutputRegex( '/The Composer autoloader was not found/' );

		do_action( 'admin_notices' );
	}
}
