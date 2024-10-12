<?php
/**
 * Upgrades to version 0.4.0
 *
 * @package WPGraphQL\Login\Admin\Upgrade
 * @since 0.4.0
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Admin\Upgrade;

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

		$success = update_option( PluginSettings::get_slug(), $existing_values );

		if ( ! $success ) {
			throw new \Exception( 'Failed to migrate plugin settings.' );
		}

		// Delete the old settings.
		foreach ( $settings_map as $old_key => $new_key ) {
			$success = delete_option( $old_key );

			if ( ! $success ) {
				throw new \Exception( 'Failed to delete old plugin settings.' );
			}
		}
	}
}
