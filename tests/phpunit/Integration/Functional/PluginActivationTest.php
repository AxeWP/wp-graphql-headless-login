<?php
/**
 * Tests that the plugin bootstraps.
 *
 * @package Tests\WPGraphQL\Login\Integration\Functional
 */

namespace Tests\WPGraphQL\Login\Integration\Functional;

use Tests\WPGraphQL\Login\TestCase;
use WPGraphQL\Login\Admin\Settings;
use WPGraphQL\Login\Main;
use WPGraphQL\Login\TypeRegistry;

/**
 * Tests the hooks registered when the plugin loads.
 */
class PluginActivationTest extends TestCase {
	/**
	 * Tests that the core hooks are registered.
	 */
	public function test_plugin_bootstraps_core_hooks(): void {
		$this->assertTrue( defined( 'WPGRAPHQL_LOGIN_PLUGIN_FILE' ) );
		$this->assertTrue( defined( 'WPGRAPHQL_LOGIN_PLUGIN_DIR' ) );
		$this->assertInstanceOf( Main::class, Main::instance() );
		$this->assertNotFalse( call_user_func( 'has_action', get_graphql_register_action(), [ TypeRegistry::class, 'init' ] ) );
	}

	/**
	 * Tests that the settings tab hooks are registered.
	 */
	public function test_settings_tab_hooks_are_registered(): void {
		$this->assertNotFalse( call_user_func( 'has_action', 'graphql_register_settings', [ Settings::class, 'register_settings_tab' ] ) );
		$this->assertNotFalse( call_user_func( 'has_action', 'admin_enqueue_scripts', [ Settings::class, 'register_admin_scripts' ] ) );
	}
}
