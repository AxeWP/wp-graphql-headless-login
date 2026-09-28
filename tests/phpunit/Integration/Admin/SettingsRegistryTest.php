<?php
/**
 * Tests the SettingsRegistry.
 *
 * @package WPGraphQL\Login\Tests\Integration\Admin
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Tests\Integration\Admin;

use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionClass;
use WPGraphQL\Login\Admin\Settings\AbstractSettings;
use WPGraphQL\Login\Admin\Settings\AccessControlSettings;
use WPGraphQL\Login\Admin\SettingsRegistry;
use WPGraphQL\Login\Tests\TestCase;

/**
 * Tests the Admin\SettingsRegistry class.
 */
#[CoversClass( SettingsRegistry::class )]
class SettingsRegistryTest extends TestCase {
	/**
	 * {@inheritDoc}
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->reset_registry();
	}

	/**
	 * {@inheritDoc}
	 */
	protected function tearDown(): void {
		$this->reset_registry();

		parent::tearDown();
	}

	/**
	 * Tests that register_settings() registers every setting in the registry with WordPress.
	 */
	public function test_register_settings_registers_each_setting_with_wordpress(): void {
		// The plugin already registered them on `init`, so start from a clean slate.
		foreach ( SettingsRegistry::get_all() as $setting ) {
			unregister_setting( AbstractSettings::SETTINGS_GROUP, $setting::get_slug() );
			$this->assertArrayNotHasKey( $setting::get_slug(), get_registered_settings() );
		}

		SettingsRegistry::register_settings();

		$registered = get_registered_settings();

		foreach ( SettingsRegistry::get_all() as $setting ) {
			$this->assertArrayHasKey( $setting::get_slug(), $registered );
		}
	}

	/**
	 * Tests that get_all() initializes the settings instances once and returns the same instances after.
	 */
	public function test_get_all_returns_memoized_settings_instances(): void {
		// Test before init should initialize the settings.
		$settings = SettingsRegistry::get_all();

		$this->assert_valid_settings( $settings );

		// Test after init should return the settings.
		$expected = $settings;
		$settings = SettingsRegistry::get_all();

		$this->assert_valid_settings( $settings );
		$this->assertSame( $expected, $settings );
	}

	/**
	 * Tests that get() returns the memoized settings instance for a slug, and null for an unknown slug.
	 */
	public function test_get_returns_settings_instance_by_slug(): void {
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

		$this->assertNull( SettingsRegistry::get( 'not-a-setting' ) );
	}

	/**
	 * Clears the registry's cached settings instances.
	 */
	private function reset_registry(): void {
		( new ReflectionClass( SettingsRegistry::class ) )->setStaticPropertyValue( 'settings', [] );
	}

	/**
	 * Asserts the settings are the expected list of settings instances.
	 *
	 * @param array<string,mixed> $settings The settings returned by the registry.
	 */
	private function assert_valid_settings( array $settings ): void {
		$this->assertNotEmpty( $settings );
		$this->assertCount( 3, $settings );

		foreach ( $settings as $setting ) {
			$this->assertInstanceOf( AbstractSettings::class, $setting );
		}
	}
}
