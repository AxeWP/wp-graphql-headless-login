<?php
/**
 * Deactivation Hook
 *
 * @package WPGraphQL\Login
 * @since 0.0.1
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login;

use WPGraphQL\Login\Admin\Settings\ProviderSettings;
use WPGraphQL\Login\Admin\Upgrade\AbstractUpgrade;
use WPGraphQL\Login\Auth\ProviderRegistry;
use WPGraphQL\Login\Settings\SettingsRegistry;

/**
 * Runs when WPGraphQL is de-activated.
 *
 * This cleans up data that WPGraphQL stores.
 *
 * @since 0.0.1
 */
function deactivation_callback(): void {
	// Fire an action when WPGraphQL is de-activating.
	do_action( 'graphql_login_deactivate' );

	// Delete data during activation.
	delete_data();
}

/**
 * Delete data on deactivation.
 *
 * @since 0.0.1
 */
function delete_data(): void {

	// Check if the plugin is set to delete data or not.
	$delete_data = graphql_login_get_setting( 'delete_data_on_deactivate' );

	// Bail if not set to delete.
	if ( empty( $delete_data ) ) {
		return;
	}

	// Initialize the settings API.
	$options = array_merge(
		[ AbstractUpgrade::VERSION_OPTION_KEY ],
		array_keys( SettingsRegistry::get_all() ),
		// The provider client configurations.
		array_map(
			static fn ( string $slug ): string => ProviderSettings::$settings_prefix . $slug,
			array_keys( ProviderRegistry::get_instance()->get_registered_providers() )
		),
	);

	foreach ( $options as $option ) {
		delete_option( $option );
	}

	// Fire an action when data is deleted.
	do_action( 'graphql_login_delete_data' );
}
