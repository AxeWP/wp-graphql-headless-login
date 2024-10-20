<?php

use WPGraphQL\Login\Admin\Settings\PluginSettings;
use WPGraphQL\Login\Admin\Settings\ProviderSettings;

/**
 * Tests access functons
 */
class AccessFunctionsTest extends \lucatume\WPBrowser\TestCase\WPTestCase {
	/**
	 * @var \WpunitTester
	 */
	protected $tester;

	/**
	 * {@inheritDoc}
	 */
	public function setUp(): void {
		codecept_debug( $GLOBALS );
		if ( ! isset( $GLOBALS['wp_filter'] ) ) {
			$GLOBALS['wp_filter'] = [];
		}

		parent::setUp();
	}

	/**
	 * Tests graphql_login_get_setting()
	 *
	 * @covers graphql_login_get_setting()
	 */
	public function testGetSetting(): void {
		$expected = true;

		update_option( PluginSettings::get_slug(), [ 'delete_data_on_deactivate' => $expected ] );

		$actual = graphql_login_get_setting( 'delete_data_on_deactivate' );

		$this->assertEquals( $expected, $actual );

		// cleanup db
		delete_option( PluginSettings::get_slug() );
	}

	/**
	 * Tests graphql_login_get_provider_settings()
	 *
	 * @covers graphql_login_get_provider_settings()
	 */
	public function testGetProviderSettings(): void {
		$expected = [
			'name'      => 'Facebook',
			'isEnabled' => false,
		];

		update_option( ProviderSettings::$settings_prefix . 'facebook', $expected );

		// reset Utils::providers
		$this->tester->reset_utils_properties();

		$actual = graphql_login_get_provider_settings( 'facebook' );

		$this->assertEquals( $expected, $actual );

		// cleanup db
		delete_option( ProviderSettings::$settings_prefix . 'facebook' );
	}
}
