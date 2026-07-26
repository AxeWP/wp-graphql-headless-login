<?php
/**
 * Tests the SettingsRegistry.
 *
 * @package Tests\WPGraphQL\Login\Integration\Settings
 */

namespace Tests\WPGraphQL\Login\Integration\Settings;

use Tests\WPGraphQL\Login\TestCase;
use WPGraphQL\Login\Admin\Settings\AbstractSettings;
use WPGraphQL\Login\Admin\Settings\AccessControlSettings;
use WPGraphQL\Login\Admin\SettingsRegistry;

/**
 * A SettingsRegistry that exposes and resets its registered settings.
 */
class MockSettings extends SettingsRegistry {
	/**
	 * Resets the registered settings.
	 */
	public static function reset(): void {
		static::$settings = null;
	}

	/**
	 * Returns the registered settings.
	 */
	public static function get_settings_property(): ?array {
		return static::$settings;
	}
}

/**
 * Tests the SettingsRegistry class.
 */
class SettingsRegistryTest extends TestCase {
	/**
	 * {@inheritDoc}
	 */
	protected function setUp(): void {
		parent::setUp();

		MockSettings::reset();
	}

	/**
	 * {@inheritDoc}
	 */
	protected function tearDown(): void {
		MockSettings::reset();

		parent::tearDown();
	}

	/**
	 * Tests `SettingsRegistry::init()`.
	 */
	public function testInit(): void {
		$has_action = '\\has_action';
		$has_action = $has_action( 'init', [ SettingsRegistry::class, 'register_settings' ] );

		// Must run after WPGraphQL's `init_registry()` at priority 11.
		// @see self::testUnsavedOptionResolvesToRegisteredDefault()
		$this->assertSame( 12, $has_action );
	}

	/**
	 * Test the register_settings method.
	 */
	public function testRegisterSettings(): void {
		MockSettings::init();
		MockSettings::register_settings();

		$this->assertValidSettings( MockSettings::get_settings_property() );
	}

	/**
	 * Test the get_all method.
	 */
	public function testGetAll(): void {
		$settings = MockSettings::get_all();

		$this->assertValidSettings( $settings );

		$expected = $settings;
		$settings = MockSettings::get_all();

		$this->assertValidSettings( $settings );
		$this->assertSame( $expected, $settings );
	}

	/**
	 * Test the get method.
	 */
	public function testGet(): void {
		$slug = AccessControlSettings::get_slug();

		$actual = MockSettings::get( $slug );

		$this->assertInstanceOf( AccessControlSettings::class, $actual );

		$settings = MockSettings::get_all();

		foreach ( $settings as $setting ) {
			$instance_slug = $setting::get_slug();
			$instance      = MockSettings::get( $instance_slug );

			$this->assertInstanceOf( AbstractSettings::class, $instance );
		}

		$expected = $actual;
		$actual   = MockSettings::get( $slug );

		$this->assertInstanceOf( AccessControlSettings::class, $actual );
		$this->assertSame( $expected, $actual );
	}

	/**
	 * Asserts the settings are valid.
	 */
	private function assertValidSettings( $settings ): void {
		$this->assertIsArray( $settings );
		$this->assertNotEmpty( $settings );
		$this->assertCount( 3, $settings );

		foreach ( $settings as $setting ) {
			$this->assertInstanceOf( AbstractSettings::class, $setting );
		}
	}
}
