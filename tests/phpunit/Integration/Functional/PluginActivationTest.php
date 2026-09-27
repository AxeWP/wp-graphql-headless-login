<?php
/**
 * Tests that the plugin bootstraps.
 *
 * @package Tests\WPGraphQL\Login\Integration\Functional
 */

namespace Tests\WPGraphQL\Login\Integration\Functional;

use Tests\WPGraphQL\Login\TestCase;
use WPGraphQL\Admin\Settings\SettingsRegistry;
use WPGraphQL\Login\Admin\Settings;

/**
 * Tests the hooks registered when the plugin loads.
 */
class PluginActivationTest extends TestCase {
	public function test_settings_tab_is_registered(): void {
		do_action( 'graphql_register_settings' );

		$registry = new SettingsRegistry();
		do_action( 'graphql_init_settings', $registry );

		$this->assertArrayHasKey( Settings::$option_group, $registry->get_settings_sections() );
	}

	public function test_settings_app_is_enqueued_on_settings_page(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		set_current_screen( 'graphql_page_graphql-settings' );

		do_action( 'admin_enqueue_scripts', 'graphql_page_graphql-settings' );

		$this->assertTrue( wp_script_is( 'wp-graphql-headless-login/admin-editor', 'enqueued' ) );

		set_current_screen( 'front' );
	}
}
