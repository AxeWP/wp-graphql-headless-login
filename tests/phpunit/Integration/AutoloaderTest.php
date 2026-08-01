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
 * An Autoloader that can reset its loaded state between tests.
 */
class MockAutoloader extends Autoloader {
	/**
	 * Resets the loaded state.
	 */
	public static function reset() {
		self::$is_loaded = false;
	}
}

/**
 * Tests Autoloader.
 */
class AutoloaderTest extends TestCase {
	/**
	 * Autoloader instance.
	 *
	 * @var \WPGraphQL\Login\Autoloader
	 */
	protected $autoloader;

	/**
	 * {@inheritDoc}
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->autoloader = new MockAutoloader();
		MockAutoloader::reset();
	}

	/**
	 * {@inheritDoc}
	 */
	protected function tearDown(): void {
		MockAutoloader::reset();
		unset( $this->autoloader );

		parent::tearDown();
	}

	/**
	 * Tests `Autoloader::autoload()`.
	 */
	public function testAutoload() {
		$this->assertTrue( $this->autoloader->autoload() );
	}

	/**
	 * Tests `Autoloader::require_autoloader()` with a valid and an invalid path.
	 */
	public function testRequireAutoloader() {
		$reflection = new \ReflectionClass( $this->autoloader );
		$property   = $reflection->getProperty( 'is_loaded' );
		$property->setAccessible( true );
		$property->setValue( $this->autoloader, false );

		$method = $reflection->getMethod( 'require_autoloader' );
		$method->setAccessible( true );

		$this->assertTrue( $method->invokeArgs( $this->autoloader, [ WPGRAPHQL_LOGIN_PLUGIN_DIR . '/vendor/autoload.php' ] ) );
		$this->assertFalse( $method->invokeArgs( $this->autoloader, [ '/path/to/invalid/autoload.php' ] ) );

		$this->expectOutputRegex( '/The Composer autoloader was not found/' );

		\call_user_func( 'do_action', 'admin_notices' );
	}
}
