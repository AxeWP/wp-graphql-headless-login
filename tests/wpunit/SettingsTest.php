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

	public function testGetAllSettings(): void {
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

	public function testSanitizeAccessControlOptions() {
		// Test sanitization
		$original = [
			'hasAccessControlAllowCredentials' => 'true',
			'hasSiteAddressInOrigin'           => 'true',
			'shouldBlockUnauthorizedDomains'   => '0',
			'customHeaders'                    => [ '*', '<strong>X-Wrapped-In-HTML</strong>' ],
		];

		$this->markTestIncomplete( 'Sanitization now happens on the endpoint, not in the settings.' );

		$actual = AccessControlSettings::sanitize_callback( $original );

		$this->assertTrue( $actual['hasAccessControlAllowCredentials'], 'hasSiteAddressInOrigin should be (bool) true.' );
		$this->assertTrue( $actual['hasSiteAddressInOrigin'], 'hasSiteAddressInOrigin should be (bool) true.' );
		$this->assertFalse( $actual['shouldBlockUnauthorizedDomains'], 'shouldBlockUnauthorizedDomains should be (bool) false.' );
		$this->assertEquals( [ '*', 'X-Wrapped-In-HTML' ], $actual['customHeaders'], 'customHeaders should be sanitized.' );

		// Test additionalAuthorizedDomains as wildcard string.
		$original['additionalAuthorizedDomains'] = '*';

		$actual = AccessControlSettings::sanitize_callback( $original );

		$this->assertEquals( [ '*' ], $actual['additionalAuthorizedDomains'], 'additionalAuthorizedDomains should be an array with a single wildcard.' );

		// Test sanitization of additionalAuthorizedDomains as string.
		$original['additionalAuthorizedDomains'] = 'https://example.com, badurl, https://example.org';

		$actual = AccessControlSettings::sanitize_callback( $original );

		$this->assertEquals( 'https://example.com', $actual['additionalAuthorizedDomains'][0], 'additionalAuthorizedDomains should be an array of sanitized values.' );
		$this->assertStringStartsWith( 'http', $actual['additionalAuthorizedDomains'][1], 'additionalAuthorizedDomains should be an array of sanitized values.' );
		$this->assertEquals( 'https://example.org', $actual['additionalAuthorizedDomains'][2], 'additionalAuthorizedDomains should be an array of sanitized values' );
	}

	protected function assertArrayNotHasKeys( $keys, $array, $message = '' ): void {
		foreach ( (array) $keys as $key ) {
			$this->assertArrayNotHasKey( $key, $array, $message );
		}
	}
}
