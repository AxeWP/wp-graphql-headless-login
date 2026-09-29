<?php
/**
 * Tests the plugin access functions.
 *
 * @package WPGraphQL\Login\Tests\Integration
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Tests\Integration;

use PHPUnit\Framework\Attributes\CoversFunction;
use WPGraphQL\Login\Admin\Settings\ProviderSettings;
use WPGraphQL\Login\Settings\PluginSettings;
use WPGraphQL\Login\Tests\TestCase;

/**
 * Tests the plugin access functions.
 */
#[CoversFunction( 'graphql_login_get_setting' )]
#[CoversFunction( 'graphql_login_get_provider_settings' )]
class AccessFunctionsTest extends TestCase {
	/**
	 * Tests that graphql_login_get_setting() returns the stored plugin setting.
	 */
	public function test_graphql_login_get_setting_returns_stored_value(): void {
		$expected = true;

		update_option( PluginSettings::get_slug(), [ 'delete_data_on_deactivate' => $expected ] );

		// Reset the memoized settings.
		$this->reset_utils_properties();

		$actual = graphql_login_get_setting( 'delete_data_on_deactivate' );

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Tests that graphql_login_get_provider_settings() returns the stored provider settings.
	 */
	public function test_graphql_login_get_provider_settings_returns_stored_settings(): void {
		$expected = [
			'name'      => 'Facebook',
			'isEnabled' => false,
		];

		update_option( ProviderSettings::$settings_prefix . 'facebook', $expected );

		// Reset the memoized provider settings.
		$this->reset_utils_properties();

		$actual = graphql_login_get_provider_settings( 'facebook' );

		$this->assertEquals( $expected, $actual );
	}
}
