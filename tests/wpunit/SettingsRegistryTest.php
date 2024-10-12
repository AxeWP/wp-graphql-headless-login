<?php

/**
 * Tests the SettingsRegistry class.
 */

use WPGraphQL\Login\Admin\Settings\AbstractSettings;
use WPGraphQL\Login\Admin\Settings\AccessControlSettings;
use WPGraphQL\Login\Admin\SettingsRegistry;

class MockSettings extends SettingsRegistry {
	public static function reset(): void {
		static::$settings = null;
	}

	public static function get_settings_property(): ?array {
		return static::$settings;
	}
}

class SettingsRegistryTest extends \Codeception\TestCase\WPTestCase {
	/**
	 * @var \WpunitTester
	 */
	protected $tester;

	protected function setup(): void {
		parent::setUp();

		MockSettings::reset();
	}

	protected function tearDown(): void {
		MockSettings::reset();

		parent::tearDown();
	}

	public function testInit(): void {
		// Assert the Action was added.
		$has_action = has_action( 'init', [ SettingsRegistry::class, 'register_settings' ] );

		$this->assertIsInt( $has_action );
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
		// Test before init should initialize the settings.
		$settings = MockSettings::get_all();

		$this->assertValidSettings( $settings );

		// Test after init should return the settings.
		$expected = $settings;
		$settings = MockSettings::get_all();

		$this->assertValidSettings( $settings );
		$this->assertSame( $expected, $settings );
	}

	/**
	 * Test the get method.
	 */
	public function testGet(): void {
		// Test before init should initialize the settings.
		$slug = AccessControlSettings::get_slug();

		$actual = MockSettings::get( $slug );

		$this->assertInstanceOf( AccessControlSettings::class, $actual );

		$settings = MockSettings::get_all();

		foreach( $settings as $setting ) {
			$instance_slug = $setting::get_slug();
			$instance = MockSettings::get( $instance_slug );

			$this->assertInstanceOf( AbstractSettings::class, $instance );
		}

		// Test after init should return the settings.
		$expected = $actual;
		$actual = MockSettings::get( $slug );

		$this->assertInstanceOf( AccessControlSettings::class, $actual );
		$this->assertSame( $expected, $actual );
	}

	/**
	 * Asserts the settings are valid.
	 */
	private function assertValidSettings( $settings ): void {
		$this->assertIsArray( $settings );
		$this->assertNotEmpty( $settings );
		$this->assertCount( 2, $settings );

		foreach( $settings as $setting ) {
			$this->assertInstanceOf( AbstractSettings::class, $setting );
		}
	}
}
