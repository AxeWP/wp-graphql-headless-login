<?php
/**
 * Upgrades to version 0.4.0
 *
 * @package WPGraphQL\Login\Admin\Upgrade
 * @since 0.4.0
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Admin\Upgrade;

use WPGraphQL\Login\Admin\Settings\AccessControlSettings;
use WPGraphQL\Login\Admin\Settings\CookieSettings;
use WPGraphQL\Login\Admin\Settings\PluginSettings;

/**
 * Class V0_4_0
 */
class V0_4_0 extends AbstractUpgrade {
	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	public static string $version = '0.4.0';

	/**
	 * {@inheritDoc}
	 */
	protected function upgrade(): void {
		// Migrate Plugin Settings.
		$this->migrate_plugin_settings();
		// Migrate Access Control Settings.
		$this->migrate_access_control_settings();
	}

	/**
	 * Migrates Plugin Settings to the new storage format.
	 *
	 * @throws \Exception Throws an exception if the migration fails.
	 */
	private function migrate_plugin_settings(): void {
		$settings_map = [
			'wp_graphql_login_settings_show_advanced_settings' => 'show_advanced_settings',
			'wp_graphql_login_settings_delete_data_on_deactivate' => 'delete_data_on_deactivate',
			'wp_graphql_login_settings_jwt_secret_key' => 'jwt_secret_key',
		];

		$existing_values = [];

		foreach ( $settings_map as $old_key => $new_key ) {
			$existing_values[ $new_key ] = get_option( $old_key, null );
		}

		$existing_values = array_filter( $existing_values );

		// If there are no existing values, there is nothing to migrate.
		if ( empty( $existing_values ) ) {
			return;
		}

		update_option( PluginSettings::get_slug(), $existing_values );

		// Delete the old settings.
		foreach ( $settings_map as $old_key => $new_key ) {
			delete_option( $old_key );
		}
	}

	/**
	 * Migrates relevant Access Control settings to Cookie settings.
	 *
	 * @throws \Exception Throws an exception if the migration fails.
	 */
	private function migrate_access_control_settings(): void {
		$setttings_map = [
			'hasAccessControlAllowCredentials' => 'hasAccessControlAllowCredentials',
		];

		$access_control_settings = get_option( AccessControlSettings::get_slug(), [] );

		// If there are no existing values, there is nothing to migrate.
		if ( empty( $access_control_settings ) ) {
			return;
		}

		$cookie_settings = get_option( CookieSettings::get_slug(), [] );

		foreach ( $setttings_map as $old_key => $new_key ) {
			if ( ! isset( $access_control_settings[ $old_key ] ) ) {
				continue;
			}

			$cookie_settings[ $new_key ] = $access_control_settings[ $old_key ];
			unset( $access_control_settings[ $old_key ] );
		}

		update_option( CookieSettings::get_slug(), $cookie_settings );
		update_option( AccessControlSettings::get_slug(), $access_control_settings );
	}
}
