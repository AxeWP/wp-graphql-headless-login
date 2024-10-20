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
		$instance   = new Settings();
		$reflection = new ReflectionClass( $instance );
		$method     = $reflection->getMethod( 'get_settings_data' );
		$method->setAccessible( true );

		$actual = $method->invoke( $instance );

		$this->assertNotEmpty( $actual );

		// Test Secret
		$expected_secret = [
			'hasKey'     => true,
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

		// Test Providers.
		$this->assertArrayHasKey( 'providers', $actual['settings'] );
		$this->assertNotEmpty( $actual['settings']['providers'] );

		$providers = $actual['settings']['providers'];

		$provider_keys = array_map(
			static fn ( string $key ) => ProviderSettings::$settings_prefix . $key,
			array_keys( ProviderRegistry::get_instance()->get_registered_providers() )
		);

		$this->assertEqualSets(
			$provider_keys,
			array_keys( $providers ),
			'Provider settings should have the same keys as the registered providers.'
		);

		// Ensure the keys are in ProviderSettings::get_config().
		$reflection        = new ReflectionClass( ProviderSettings::class );
		$provider_settings = $reflection->getProperty( 'config' );
		$provider_settings->setAccessible( true );
		// Set the config to null to force a reload.
		$provider_settings->setValue( [] );

		$provider_settings = ProviderSettings::get_config();

		foreach ( $provider_keys as $key ) {
			$this->assertArrayHasKey( $key, $provider_settings, 'The provider key ' . $key . ' should be in the provider settings.' );
		}
	}
}
