<?php
/**
 * Tests the v0.4.0 upgrade routine.
 *
 * @package WPGraphQL\Login\Tests\Integration\Admin\Upgrade
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Tests\Integration\Admin\Upgrade;

use PHPUnit\Framework\Attributes\CoversClass;
use WPGraphQL\Login\Admin\Upgrade\AbstractUpgrade;
use WPGraphQL\Login\Admin\Upgrade\V0_4_0;
use WPGraphQL\Login\Settings\AccessControlSettings;
use WPGraphQL\Login\Settings\CookieSettings;
use WPGraphQL\Login\Settings\PluginSettings;
use WPGraphQL\Login\Tests\TestCase;

/**
 * Tests the Admin\Upgrade\V0_4_0 class.
 */
#[CoversClass( V0_4_0::class )]
class V0_4_0Test extends TestCase {
	/**
	 * Tests that the upgrade moves the legacy plugin and access control settings to their new options.
	 */
	public function test_upgrade_migrates_legacy_settings(): void {
		// Set the old settings.
		update_option( 'wp_graphql_login_settings_show_advanced_settings', true );
		update_option( 'wp_graphql_login_settings_delete_data_on_deactivate', true );
		update_option( 'wp_graphql_login_settings_jwt_secret_key', 'secret' );

		// The registered sanitize callback would strip the legacy key, so bypass it to seed the old value.
		remove_all_filters( 'sanitize_option_' . AccessControlSettings::get_slug() );
		update_option( AccessControlSettings::get_slug(), [ 'hasAccessControlAllowCredentials' => true ] );

		// Set the version to < 0.4.0.
		update_option( AbstractUpgrade::VERSION_OPTION_KEY, '0.3.0' );

		$upgrade = new V0_4_0();

		$this->assertTrue( $upgrade->run() );
		$this->assertSame( '0.4.0', get_option( AbstractUpgrade::VERSION_OPTION_KEY ) );

		// Check the new settings.
		$this->assertEquals(
			[
				'show_advanced_settings'    => true,
				'delete_data_on_deactivate' => true,
				'jwt_secret_key'            => 'secret',
			],
			get_option( PluginSettings::get_slug() )
		);
		$this->assertTrue(
			get_option( CookieSettings::get_slug() )['hasAccessControlAllowCredentials']
		);

		// Check the old settings.
		$this->assertFalse( get_option( 'wp_graphql_login_settings_show_advanced_settings' ) );
		$this->assertFalse( get_option( 'wp_graphql_login_settings_delete_data_on_deactivate' ) );
		$this->assertFalse( get_option( 'wp_graphql_login_settings_jwt_secret_key' ) );
		$this->assertArrayNotHasKey( 'hasAccessControlAllowCredentials', get_option( AccessControlSettings::get_slug(), [] ) );
	}

	/**
	 * Tests that the upgrade leaves the current settings untouched when there are no legacy settings to migrate.
	 */
	public function test_upgrade_without_legacy_settings_changes_nothing(): void {
		update_option( AbstractUpgrade::VERSION_OPTION_KEY, '0.3.0' );
		update_option( CookieSettings::get_slug(), [ 'hasLogoutMutation' => true ] );

		// No access control settings at all.
		$this->assertTrue( ( new V0_4_0() )->run() );

		$this->assertFalse( get_option( PluginSettings::get_slug(), false ) );
		$this->assertSame( [ 'hasLogoutMutation' => true ], get_option( CookieSettings::get_slug() ) );

		// Access control settings without the legacy key.
		update_option( AbstractUpgrade::VERSION_OPTION_KEY, '0.3.0' );
		update_option( AccessControlSettings::get_slug(), [ 'shouldBlockUnauthorizedDomains' => true ] );

		$this->assertTrue( ( new V0_4_0() )->run() );

		$this->assertSame( [ 'shouldBlockUnauthorizedDomains' => true ], get_option( AccessControlSettings::get_slug() ) );
		$this->assertSame( [ 'hasLogoutMutation' => true ], get_option( CookieSettings::get_slug() ) );
	}
}
