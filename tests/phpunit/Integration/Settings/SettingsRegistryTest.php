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
 * Tests the SettingsRegistry class.
 */
class SettingsRegistryTest extends TestCase {
	/**
	 * {@inheritDoc}
	 */
	protected function setUp(): void {
		parent::setUp();

		self::reset_registry();
	}

	/**
	 * {@inheritDoc}
	 */
	protected function tearDown(): void {
		self::reset_registry();

		parent::tearDown();
	}

	/**
	 * Clears the registry's cached settings instances.
	 */
	private static function reset_registry(): void {
		\Closure::bind(
			static function (): void {
				SettingsRegistry::$settings = [];
			},
			null,
			SettingsRegistry::class
		)();
	}

	/**
	 * Test the register_settings method.
	 */
	public function testRegisterSettings(): void {
		SettingsRegistry::register_settings();

		$registered = get_registered_settings();

		foreach ( SettingsRegistry::get_all() as $setting ) {
			$this->assertArrayHasKey( $setting::get_slug(), $registered );
		}
	}

	/**
	 * Test the get_all method.
	 */
	public function testGetAll(): void {
		// Test before init should initialize the settings.
		$settings = SettingsRegistry::get_all();

		$this->assertValidSettings( $settings );

		// Test after init should return the settings.
		$expected = $settings;
		$settings = SettingsRegistry::get_all();

		$this->assertValidSettings( $settings );
		$this->assertSame( $expected, $settings );
	}

	/**
	 * Test the get method.
	 */
	public function testGet(): void {
		// Test before init should initialize the settings.
		$slug = AccessControlSettings::get_slug();

		$actual = SettingsRegistry::get( $slug );

		$this->assertInstanceOf( AccessControlSettings::class, $actual );

		$settings = SettingsRegistry::get_all();

		foreach ( $settings as $setting ) {
			$instance_slug = $setting::get_slug();
			$instance      = SettingsRegistry::get( $instance_slug );

			$this->assertInstanceOf( AbstractSettings::class, $instance );
		}

		// Test after init should return the settings.
		$expected = $actual;
		$actual   = SettingsRegistry::get( $slug );

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
