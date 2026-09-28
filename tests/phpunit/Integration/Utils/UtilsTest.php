<?php
/**
 * Tests the Utils class.
 *
 * @package WPGraphQL\Login\Tests\Integration\Utils
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Tests\Integration\Utils;

use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionClass;
use WPGraphQL\Login\Admin\Settings\AccessControlSettings;
use WPGraphQL\Login\Admin\Settings\CookieSettings;
use WPGraphQL\Login\Admin\Settings\PluginSettings;
use WPGraphQL\Login\Admin\Settings\ProviderSettings;
use WPGraphQL\Login\Admin\SettingsRegistry;
use WPGraphQL\Login\Tests\Fixtures\FooConditionalCookieSettings;
use WPGraphQL\Login\Tests\TestCase;
use WPGraphQL\Login\Utils\Utils;

/**
 * Tests the Utils\Utils class.
 */
#[CoversClass( Utils::class )]
class UtilsTest extends TestCase {
	/**
	 * {@inheritDoc}
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->reset_utils_properties();
	}

	/**
	 * Tests that get_setting() returns the default, stored, and filtered plugin setting values.
	 */
	public function test_get_setting_returns_default_stored_and_filtered_values(): void {
		// Test the default value.
		$actual = Utils::get_setting( 'delete_data_on_deactivate' );

		$this->assertFalse( $actual, 'Default value should be false' );

		// Test db value.
		$expected = true;
		update_option( PluginSettings::get_slug(), [ 'delete_data_on_deactivate' => $expected ] );
		$this->reset_utils_properties();

		$actual = Utils::get_setting( 'delete_data_on_deactivate' );

		$this->assertEquals( $expected, $actual, 'DB value should be true' );

		// Test filter.
		add_filter( 'graphql_login_setting', [ $this, 'setting_filter_callback' ], 10, 2 );
		$this->reset_utils_properties();

		$actual = Utils::get_setting( 'delete_data_on_deactivate' );

		$this->assertFalse( $actual, 'Filter value should be false' );
	}

	/**
	 * Tests that update_plugin_setting() stores the plugin setting.
	 */
	public function test_update_plugin_setting_stores_value(): void {
		// Test db value.
		$expected = true;
		Utils::update_plugin_setting( 'delete_data_on_deactivate', $expected );
		$this->reset_utils_properties();

		$actual = Utils::get_setting( 'delete_data_on_deactivate' );

		$this->assertEquals( $expected, $actual, 'DB value should be true' );

		// Test an unknown setting.
		$this->assertFalse( Utils::update_plugin_setting( 'not_a_setting', true ) );
		$this->assertArrayNotHasKey( 'not_a_setting', get_option( PluginSettings::get_slug() ) );
	}

	/**
	 * Tests that get_cookie_setting() only returns the stored values once the settings they depend on are enabled.
	 */
	public function test_get_cookie_setting_respects_conditional_logic(): void {
		update_option(
			CookieSettings::get_slug(),
			[
				'hasAccessControlAllowCredentials' => true,
				'hasLogoutMutation'                => true,
				'cookieDomain'                     => '.example.com',
			]
		);

		// `hasAccessControlAllowCredentials` requires `shouldBlockUnauthorizedDomains`, which the other settings require in turn.
		update_option( AccessControlSettings::get_slug(), [ 'shouldBlockUnauthorizedDomains' => false ] );

		$this->assertFalse( Utils::get_cookie_setting( 'hasAccessControlAllowCredentials' ) );
		$this->assertFalse( Utils::get_cookie_setting( 'hasLogoutMutation' ) );
		$this->assertSame( 'default', Utils::get_cookie_setting( 'cookieDomain', 'default' ) );

		update_option( AccessControlSettings::get_slug(), [ 'shouldBlockUnauthorizedDomains' => true ] );

		$this->assertTrue( Utils::get_cookie_setting( 'hasAccessControlAllowCredentials' ) );
		$this->assertTrue( Utils::get_cookie_setting( 'hasLogoutMutation' ) );
		$this->assertSame( '.example.com', Utils::get_cookie_setting( 'cookieDomain', 'default' ) );

		// Unset values use the default.
		$this->assertSame( 'default', Utils::get_cookie_setting( 'sameSiteOption', 'default' ) );

		// Unknown settings use the default.
		$this->assertSame( 'default', Utils::get_cookie_setting( 'notASetting', 'default' ) );

		// The value can be filtered.
		add_filter(
			'graphql_login_cookie_setting',
			static fn ( $value, string $option_name ) => 'cookieDomain' === $option_name ? '.filtered.com' : $value,
			10,
			2
		);

		$this->assertSame( '.filtered.com', Utils::get_cookie_setting( 'cookieDomain' ) );
	}

	/**
	 * Tests each conditional logic operator, and that a dependency on an unknown settings group is never met.
	 */
	public function test_conditional_logic_operators(): void {
		$registry = new ReflectionClass( SettingsRegistry::class );
		$original = $registry->getStaticPropertyValue( 'settings' );

		$registry->setStaticPropertyValue(
			'settings',
			array_merge( SettingsRegistry::get_all(), [ CookieSettings::get_slug() => new FooConditionalCookieSettings() ] )
		);

		$fields = [ 'whenEqual', 'whenNotEqual', 'whenGreater', 'whenLess', 'whenGreaterOrEqual', 'whenLessOrEqual', 'whenUnknownOp', 'whenMissingGroup' ];

		// Store every dependent field as enabled, bypassing the sanitization registered for the real config.
		remove_all_filters( 'sanitize_option_' . CookieSettings::get_slug() );
		update_option( CookieSettings::get_slug(), array_fill_keys( $fields, true ) );

		try {
			// `level` uses its default of 5.
			$actual = array_combine( $fields, array_map( [ Utils::class, 'get_cookie_setting' ], $fields ) );

			$this->assertSame(
				[
					'whenEqual'          => true,
					'whenNotEqual'       => false,
					'whenGreater'        => true,
					'whenLess'           => true,
					'whenGreaterOrEqual' => false,
					'whenLessOrEqual'    => false,
					'whenUnknownOp'      => false,
					'whenMissingGroup'   => false,
				],
				$actual
			);
		} finally {
			$registry->setStaticPropertyValue( 'settings', $original );
		}
	}

	/**
	 * Tests that get_access_control_setting() returns the default, stored, and filtered access control setting values.
	 */
	public function test_get_access_control_setting_returns_default_stored_and_filtered_values(): void {
		$expected = [];

		// Test the default value.
		$actual = Utils::get_access_control_setting( 'hasSiteAddressInOrigin' );

		$this->assertFalse( $actual, 'Default value should be false' );

		// Test db value.
		$expected['hasSiteAddressInOrigin'] = true;
		update_option( AccessControlSettings::get_slug(), $expected );
		$this->reset_utils_properties();

		$actual = Utils::get_access_control_setting( 'hasSiteAddressInOrigin' );

		$this->assertEquals( $expected['hasSiteAddressInOrigin'], $actual, 'DB value should be true' );

		// Test filter.
		add_filter( 'graphql_login_access_control_settings', [ $this, 'access_control_settings_filter_callback' ], 10, 2 );
		$this->reset_utils_properties();

		$actual = Utils::get_access_control_setting( 'hasSiteAddressInOrigin' );

		$this->assertFalse( $actual, 'Filter value should be false' );
	}

	/**
	 * Tests that get_provider_settings() returns the default, stored, and filtered provider settings.
	 */
	public function test_get_provider_settings_returns_default_stored_and_filtered_values(): void {
		// Test the default value.
		$actual = Utils::get_provider_settings( 'facebook' );

		$this->assertEmpty( $actual, 'Default value should be an empty array' );

		// Test db value.
		$expected = [
			'name'      => 'Facebook',
			'isEnabled' => false,
		];

		$this->set_client_config( 'facebook', $expected );

		$actual = Utils::get_provider_settings( 'facebook' );

		$this->assertEquals( $expected, $actual, 'DB value should be returned' );

		// Test filter.
		add_filter( 'graphql_login_provider_settings', [ $this, 'provider_settings_filter_callback' ], 10, 2 );
		$this->reset_utils_properties();

		$actual = Utils::get_provider_settings( 'facebook' );

		$this->assertTrue( $actual['isEnabled'], 'Filter value should be true' );
	}

	/**
	 * Tests that get_all_provider_settings() returns the default, stored, and filtered settings for every provider.
	 */
	public function test_get_all_provider_settings_returns_default_stored_and_filtered_values(): void {
		// Test the default value.
		$actual = Utils::get_all_provider_settings();

		$this->assertArrayHasKey( 'facebook', $actual, 'Default value should have the keys for all providers' );

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

		update_option( ProviderSettings::$settings_prefix . 'facebook', $expected['facebook'] );
		update_option( ProviderSettings::$settings_prefix . 'google', $expected['google'] );
		$this->reset_utils_properties();

		$actual = Utils::get_all_provider_settings();

		$this->assertEquals( $expected['facebook'], $actual['facebook'], 'DB value should exist' );
		$this->assertEquals( $expected['google'], $actual['google'], 'DB value should exist' );

		// Test filter.
		add_filter( 'graphql_login_provider_settings', [ $this, 'provider_settings_filter_callback' ], 10, 2 );
		$this->reset_utils_properties();

		$actual = Utils::get_all_provider_settings();

		$this->assertTrue( $actual['facebook']['isEnabled'], 'Filter value should be true' );
	}

	/**
	 * Tests that is_current_user() only matches the logged-in user.
	 */
	public function test_is_current_user_matches_only_logged_in_user(): void {
		$user = $this->factory()->user->create_and_get();

		// Test logged out.
		$actual = Utils::is_current_user( $user->ID );

		$this->assertFalse( $actual, 'Should be false when logged out' );

		// Test logged out with a user object.
		$actual = Utils::is_current_user( $user );

		$this->assertFalse( $actual, 'Should be false when logged out' );

		// Test logged in.
		wp_set_current_user( $user->ID );

		// With a bad user ID.
		$actual = Utils::is_current_user( 999252 );

		$this->assertFalse( $actual, 'Should be false when logged in with bad user id' );

		// With a different user.
		$test_user = $this->factory()->user->create_and_get();

		$actual = Utils::is_current_user( $test_user->ID );

		$this->assertFalse( $actual, 'Should be false when logged in with different user id' );

		// With the same user.
		$actual = Utils::is_current_user( $user->ID );

		$this->assertTrue( $actual, 'Should be true when logged in' );

		// With no user.
		$actual = Utils::is_current_user( 0 );
		$this->assertFalse( $actual, 'Should be false when logged in with no user' );
	}

	/**
	 * Callback for the `graphql_login_setting` filter.
	 *
	 * @param mixed  $value   The setting value.
	 * @param string $setting The setting name.
	 *
	 * @return mixed
	 */
	public function setting_filter_callback( $value, string $setting ) {
		if ( 'delete_data_on_deactivate' === $setting ) {
			return false;
		}
		return $value;
	}

	/**
	 * Callback for the `graphql_login_provider_settings` filter.
	 *
	 * @param array<string,mixed> $settings The provider settings.
	 * @param string              $slug     The provider slug.
	 *
	 * @return array<string,mixed>
	 */
	public function provider_settings_filter_callback( array $settings, string $slug ): array {
		if ( 'facebook' === $slug ) {
			$settings['isEnabled'] = true;
		}
		return $settings;
	}

	/**
	 * Callback for the `graphql_login_access_control_settings` filter.
	 *
	 * @param array<string,mixed> $settings The access control settings.
	 *
	 * @return array<string,mixed>
	 */
	public function access_control_settings_filter_callback( array $settings ): array {
		$settings['hasSiteAddressInOrigin'] = false;

		return $settings;
	}
}
