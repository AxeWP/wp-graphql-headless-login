<?php
/**
 * Tests the plugin settings.
 *
 * @package WPGraphQL\Login\Tests\Integration\Admin
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Tests\Integration\Admin;

use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionClass;
use ReflectionMethod;
use WPGraphQL\Admin\Settings\SettingsRegistry as WPGraphQLSettingsRegistry;
use WPGraphQL\Login\Admin\Settings;
use WPGraphQL\Login\Admin\Settings\AccessControlSettings;
use WPGraphQL\Login\Admin\Settings\PluginSettings;
use WPGraphQL\Login\Admin\Settings\ProviderSettings;
use WPGraphQL\Login\Auth\ProviderRegistry;
use WPGraphQL\Login\Tests\TestCase;

/**
 * Tests the Admin\Settings class.
 */
#[CoversClass( Settings::class )]
class SettingsTest extends TestCase {
	/**
	 * Tests that the settings section is registered to the WPGraphQL settings registry.
	 */
	public function test_settings_section_is_registered_with_wpgraphql(): void {
		do_action( 'graphql_register_settings' );

		$registry = new WPGraphQLSettingsRegistry();
		do_action( 'graphql_init_settings', $registry );

		$this->assertArrayHasKey( Settings::$option_group, $registry->get_settings_sections() );
	}

	/**
	 * Tests that the settings app is enqueued on the WPGraphQL settings screen.
	 */
	public function test_settings_app_is_enqueued_on_settings_page(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		set_current_screen( 'graphql_page_graphql-settings' );

		do_action( 'admin_enqueue_scripts', 'graphql_page_graphql-settings' );

		$this->assertTrue( wp_script_is( 'wp-graphql-headless-login/admin-editor', 'enqueued' ) );
	}

	/**
	 * Tests that options.php can't overwrite the plugin settings with the section's empty form.
	 */
	public function test_options_page_cannot_save_settings(): void {
		global $new_allowed_options;

		// Core's `option_update_filter()` adds registered settings back at priority 10, but is only hooked in wp-admin.
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		add_filter( 'allowed_options', 'option_update_filter' );
		$new_allowed_options = [ Settings::$option_group => [ Settings::$option_group ] ]; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$allowed = apply_filters( 'allowed_options', [ 'general' => [ 'blogname' ] ] );

		remove_filter( 'allowed_options', 'option_update_filter' );
		$new_allowed_options = []; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$this->assertSame( [ 'general' => [ 'blogname' ] ], $allowed );
	}

	/**
	 * Tests that the data passed to the settings app includes the secret meta, a valid nonce, and every settings group.
	 */
	public function test_get_settings_data_includes_secret_nonce_and_settings(): void {
		$method = new ReflectionMethod( Settings::class, 'get_settings_data' );

		$actual = $method->invoke( new Settings() );

		$this->assertNotEmpty( $actual );

		$expected_secret = [
			'hasKey'     => true,
			'isConstant' => false,
		];

		$this->assertArrayHasKey( 'secret', $actual );
		$this->assertEquals( $expected_secret, $actual['secret'] );

		$this->assertArrayHasKey( 'nonce', $actual );
		$nonce = $actual['nonce'];

		$this->assertTrue( (bool) wp_verify_nonce( $nonce, 'wp_graphql_settings' ) );

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

		// Ensure the keys are in a freshly-loaded ProviderSettings::get_config().
		( new ReflectionClass( ProviderSettings::class ) )->setStaticPropertyValue( 'config', [] );

		$provider_settings = ProviderSettings::get_config();

		foreach ( $provider_keys as $key ) {
			$this->assertArrayHasKey( $key, $provider_settings, 'The provider key ' . $key . ' should be in the provider settings.' );
		}
	}
}
