<?php

use WPGraphQL\Login\Admin\Settings;
use WPGraphQL\Login\Utils\Utils;

/**
 * Tests Utils class
 */
class UtilsTest extends \Codeception\TestCase\WPTestCase {
	public $tester;

	/**
	 * {@inheritDoc}
	 */
	public function setUp(): void {
		parent::setUp();
		$this->tester->reset_utils_properties();
	}

	/**
	 * {@inheritDoc}
	 */
	public function tearDown(): void {
		parent::tearDown();
	}

	/**
	 * Tests Utils::get_setting()
	 *
	 * @covers \WPGraphQL\Login\Utils\Utils
	 */
	public function testGetSetting() : void {
		// Test default value (false)
		$actual = Utils::get_setting( 'delete_data_on_deactivate' );

		$this->assertFalse( $actual, 'Default value should be false' );

		// Test db value.
		$expected = true;
		update_option( Settings::$settings_prefix . 'delete_data_on_deactivate', $expected );
		$this->tester->reset_utils_properties();

		$actual = Utils::get_setting( 'delete_data_on_deactivate' );

		$this->assertEquals( $expected, $actual, 'DB value should be true' );

		// Test filter.
		add_filter( 'graphql_login_setting', [ $this, 'setting_filter_callback' ], 10, 2 );
		$this->tester->reset_utils_properties();

		$actual = Utils::get_setting( 'delete_data_on_deactivate' );

		$this->assertFalse( $actual, 'Filter value should be false' );

		remove_filter( 'graphql_login_setting', [ $this, 'setting_filter_callback' ], 10 );

		// cleanup db
		delete_option( Settings::$settings_prefix . 'delete_data_on_deactivate' );
	}

	/**
	 * Tests Utils::get_provider_settings()
	 *
	 * @covers \WPGraphQL\Login\Utils\Utils
	 */
	public function testGetProviderSettings() : void {
		// Test default value ([])
		$actual = Utils::get_provider_settings( 'facebook' );

		$this->assertEmpty( $actual, 'Default value should be an empty array' );

		// Test db value.
		$expected = [
			'name'      => 'Facebook',
			'isEnabled' => false,
		];

		$this->tester->set_client_config( 'facebook', $expected );

		$actual = Utils::get_provider_settings( 'facebook' );

		$this->assertEquals( $expected, $actual, 'DB value should be true' );

		// Test filter.
		add_filter( 'graphql_login_provider_settings', [ $this, 'provider_settings_filter_callback' ], 10, 2 );
		$this->tester->reset_utils_properties();

		$actual = Utils::get_provider_settings( 'facebook' );

		$this->assertTrue( $actual['isEnabled'], 'Filter value should be false' );

		remove_filter( 'graphql_login_provider_settings', [ $this, 'provider_settings_filter_callback' ], 10 );

		// cleanup db
		delete_option( Settings::$provider_settings_prefix . 'facebook' );
	}

	/**
	 * Tests Utils::get_provider_settings()
	 *
	 * @covers \WPGraphQL\Login\Utils\Utils
	 */
	public function testGetAllProviderSettings() {
		// Test default value ([])
		$actual = Utils::get_all_provider_settings();

		$this->assertArrayHasKey( 'facebook', $actual, 'Default value have the keys for all providers' );

		// Test db value.
		$expected = [
			'facebook' => [
				'name'      => 'Facebook',
				'isEnabled' => false,
			],
			'google'   => [
				'name'      => 'Google',
				'isEnabled' => false,
			],
		];

		update_option( Settings::$provider_settings_prefix . 'facebook', $expected['facebook'] );
		update_option( Settings::$provider_settings_prefix . 'google', $expected['google'] );
		$this->tester->reset_utils_properties();

		$actual = Utils::get_all_provider_settings();

		$this->assertEquals( $expected['facebook'], $actual['facebook'], 'DB value should exist' );
		$this->assertEquals( $expected['google'], $actual['google'], 'DB value should exist' );

		// Test filter.
		add_filter( 'graphql_login_provider_settings', [ $this, 'provider_settings_filter_callback' ], 10, 2 );
		$this->tester->reset_utils_properties();

		$actual = Utils::get_all_provider_settings();

		$this->assertTrue( $actual['facebook']['isEnabled'], 'Filter value should be false' );

		remove_filter( 'graphql_login_provider_settings', [ $this, 'provider_settings_filter_callback' ], 10 );

		// cleanup db
		delete_option( Settings::$provider_settings_prefix . 'facebook' );
		delete_option( Settings::$provider_settings_prefix . 'google' );
	}

	public function testIsCurrentUser() : void {
		$user = $this->factory()->user->create_and_get();

		// Test logged out
		$actual = Utils::is_current_user( $user->ID );

		$this->assertFalse( $actual, 'Should be false when logged out' );

		// Test logged out with user Object
		$actual = Utils::is_current_user( $user );

		$this->assertFalse( $actual, 'Should be false when logged out' );

		// Test logged in
		wp_set_current_user( $user->ID );

		// With bad user id
		$actual = Utils::is_current_user( 999252 );

		$this->assertFalse( $actual, 'Should be false when logged in with bad user id' );

		// With different user
		$test_user = $this->factory()->user->create_and_get();

		$actual = Utils::is_current_user( $test_user->ID );

		$this->assertFalse( $actual, 'Should be false when logged in with different user id' );

		// With same user
		$actual = Utils::is_current_user( $user->ID );

		$this->assertTrue( $actual, 'Should be true when logged in' );
	}

	public function setting_filter_callback( $value, string $setting ) {
		if ( 'delete_data_on_deactivate' === $setting ) {
			return false;
		}
		return $value;
	}

	public function provider_settings_filter_callback( array $settings, string $slug ) {
		if ( 'facebook' === $slug ) {
			$settings['isEnabled'] = true;
		}
		return $settings;
	}

}
