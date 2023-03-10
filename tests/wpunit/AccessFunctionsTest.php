<?php

use WPGraphQL\Login\Admin\Settings\PluginSettings;
use WPGraphQL\Login\Admin\Settings\ProviderSettings;

/**
 * Tests access functons
 */
class AccessFunctionsTest extends \Codeception\TestCase\WPTestCase {
	public $tester;

	/**
	 * {@inheritDoc}
	 */
	public function setUp(): void {
		parent::setUp();
	}

	/**
	 * {@inheritDoc}
	 */
	public function tearDown(): void {
		parent::tearDown();
	}

	/**
	 * Tests graphql_login_get_setting()
	 *
	 * @covers graphql_login_get_setting()
	 */
	public function testGetSetting() : void {
		$expected = true;

		update_option( PluginSettings::$settings_prefix . 'delete_data_on_deactivate', $expected );

		$actual = graphql_login_get_setting( 'delete_data_on_deactivate' );

		$this->assertEquals( $expected, $actual );

		// cleanup db
		delete_option( PluginSettings::$settings_prefix . 'delete_data_on_deactivate' );
	}

	/**
	 * Tests graphql_login_get_provider_settings()
	 *
	 * @covers graphql_login_get_provider_settings()
	 */
	public function testGetProviderSettings() : void {
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
