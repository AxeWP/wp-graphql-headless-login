<?php
/**
 * Tests that the plugin bootstraps.
 *
 * @package Tests\WPGraphQL\Login\Integration\Functional
 */

namespace Tests\WPGraphQL\Login\Integration\Functional;

use Tests\WPGraphQL\Login\TestCase;
use WPGraphQL\Login\Admin\Settings;
use WPGraphQL\Login\TypeRegistry;

/**
 * Tests the hooks registered when the plugin loads.
 */
class PluginActivationTest extends TestCase {
	public function test_type_registry_hook_is_registered(): void {
		$this->assertNotFalse( has_action( get_graphql_register_action(), [ TypeRegistry::class, 'init' ] ) );
	}

	public function test_settings_tab_hooks_are_registered(): void {
		$this->assertNotFalse( has_action( 'graphql_register_settings', [ Settings::class, 'register_settings_tab' ] ) );
		$this->assertNotFalse( has_action( 'admin_enqueue_scripts', [ Settings::class, 'register_admin_scripts' ] ) );
	}
}
