<?php
/**
 * Tests the plugin access functions.
 *
 * @package Tests\WPGraphQL\Login\Integration
 */

namespace Tests\WPGraphQL\Login\Integration;

use Tests\WPGraphQL\Login\TestCase;
use WPGraphQL\Login\Admin\Settings\PluginSettings;
use WPGraphQL\Login\Admin\Settings\ProviderSettings;

/**
 * Tests the plugin access functions.
 */
class AccessFunctionsTest extends TestCase {
	/**
	 * {@inheritDoc}
	 */
	public function setUp(): void {
		if ( ! isset( $GLOBALS['wp_filter'] ) ) {
			$GLOBALS['wp_filter'] = [];
		}

		parent::setUp();
	}

	/**
	 * Tests graphql_login_get_setting()
	 */
	public function testGetSetting(): void {
		$expected = true;

		\call_user_func( 'update_option', PluginSettings::get_slug(), [ 'delete_data_on_deactivate' => $expected ] );

		$actual = graphql_login_get_setting( 'delete_data_on_deactivate' );

		$this->assertEquals( $expected, $actual );

		\call_user_func( 'delete_option', PluginSettings::get_slug() );
	}

	/**
	 * Tests graphql_login_get_provider_settings()
	 */
	public function testGetProviderSettings(): void {
		$expected = [
			'name'      => 'Facebook',
			'isEnabled' => false,
		];

		\call_user_func( 'update_option', ProviderSettings::$settings_prefix . 'facebook', $expected );

		$this->reset_utils_properties();

		$actual = graphql_login_get_provider_settings( 'facebook' );

		$this->assertEquals( $expected, $actual );

		\call_user_func( 'delete_option', ProviderSettings::$settings_prefix . 'facebook' );
	}
}
