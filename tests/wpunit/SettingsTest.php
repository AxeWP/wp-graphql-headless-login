<?php

use WPGraphQL\Login\Admin\Settings;
use WPGraphQL\Login\Admin\Settings\AccessControlSettings;
use WPGraphQL\Login\Admin\Settings\PluginSettings;
use WPGraphQL\Login\Admin\Settings\ProviderSettings;
use WPGraphQL\Login\Auth\ProviderRegistry;

/**
 * Test Settings\Settings class
 */
class SettingsTest extends \lucatume\WPBrowser\TestCase\WPTestCase {
	/**
	 * @var \WpunitTester
	 */
	public $tester;

	/**
	 * Tests that the Settings tab is registered.
	 */
	public function testGetSettingsData(): void {
		$instance = new Settings();
		$reflection = new ReflectionClass( $instance );
		$method = $reflection->getMethod( 'get_settings_data' );
		$method->setAccessible( true );

		$actual = $method->invoke( $instance );

		$this->assertNotEmpty( $actual );

		// Test Secret
		$expected_secret = [
			'hasKey' => true,
			'isConstant' => false,
		];
		
		$this->assertArrayHasKey( 'secret', $actual );
		$this->assertEquals( $expected_secret, $actual['secret'] );

		// Test Nonce
		$this->assertArrayHasKey( 'nonce', $actual );
		$nonce = $actual['nonce'];
		
		$this->assertTrue( (bool) wp_verify_nonce( $nonce, 'wp_graphql_settings' ) );

		// Test Settings
		$this->assertArrayHasKey( 'settings', $actual );
		
		$expected_settings = [
			AccessControlSettings::get_slug(),
			PluginSettings::get_slug(),
		];

		foreach ( $expected_settings as $setting ) {
			$this->assertArrayHasKey( $setting, $actual['settings'] );
			$this->assertNotEmpty( $actual['settings'][ $setting ] );
		}


		$this->assertArrayHasKey( 'providers', $actual['settings'] );
	}

	public function testGetAllSettings() : void {
		$this->markTestIncomplete( 'Settings now have an abstract class with a different structure.' );
		/**
		 * Clear static setting variables.
		 */
		$reflection          = new ReflectionClass( AccessControlSettings::class );
		$reflection_property = $reflection->getProperty( 'config' );
		$reflection_property->setAccessible( true );
		$reflection_property->setValue( [] );
		$reflection_property = $reflection->getProperty( 'args' );
		$reflection_property->setAccessible( true );
		$reflection_property->setValue( [] );

		$reflection          = new ReflectionClass( PluginSettings::class );
		$reflection_property = $reflection->getProperty( 'config' );
		$reflection_property->setAccessible( true );
		$reflection_property->setValue( [] );
		$reflection_property = $reflection->getProperty( 'args' );
		$reflection_property->setAccessible( true );
		$reflection_property->setValue( [] );

		$reflection          = new ReflectionClass( ProviderSettings::class );
		$reflection_property = $reflection->getProperty( 'config' );
		$reflection_property->setAccessible( true );
		$reflection_property->setValue( [] );
		$reflection_property = $reflection->getProperty( 'args' );
		$reflection_property->setAccessible( true );
		$reflection_property->setValue( [] );

		$settings = Settings::get_all_settings();

		// Test Access Control.
		codecept_debug( $settings['access_control'] );
		$this->assertArrayHasKey( 'access_control', $settings );
		$this->assertNotEmpty( $settings['access_control'][ AccessControlSettings::get_slug() ] );
		$this->assertArrayNotHasKeys(
			[
				'advanced',
				'default',
				'help',
				'label',
				'order',
				'required',
			],
			$settings['access_control'][ AccessControlSettings::get_slug() ]['show_in_rest']['schema']['properties'],
			'Access Control settings should not have excluded keys.'
		);

		$config_keys = array_keys( AccessControlSettings::get_config() );

		$this->assertEqualSets(
			$config_keys,
			array_keys( $settings['access_control'][ AccessControlSettings::get_slug() ]['show_in_rest']['schema']['properties'] ),
			'Access Control settings should have the same keys as the config.'
		);

		// Test Plugin
		codecept_debug( $settings['plugin'] );
		$this->assertArrayHasKey( 'plugin', $settings );
		$this->assertNotEmpty( $settings['plugin'] );
		$this->assertArrayNotHasKeys(
			[
				'advanced',
				'help',
				'label',
				'order',
				'hidden',
			],
			$settings['plugin'],
			'Plugin settings should not have excluded keys.'
		);

		$config_keys = array_keys( PluginSettings::get_config() );
		$this->assertEqualSets(
			$config_keys,
			array_keys( $settings['plugin'] ),
			'Plugin settings should have the same keys as the config.'
		);

		// Test Providers
		codecept_debug( $settings['providers'] );
		$this->assertArrayHasKey( 'providers', $settings );
		$this->assertNotEmpty( $settings['providers'] );

		$provider_keys = array_map(
			static fn ( string $key ) => ProviderSettings::$settings_prefix . $key,
			array_keys( ProviderRegistry::get_instance()->get_registered_providers() )
		);

		$this->assertEqualSets(
			$provider_keys,
			array_keys( $settings['providers'] ),
			'Provider settings should have the same keys as the registered providers.'
		);
	}

	protected function assertArrayNotHasKeys( $keys, $array, $message = '' ): void {
		foreach ( (array) $keys as $key ) {
			$this->assertArrayNotHasKey( $key, $array, $message );
		}
	}
}
