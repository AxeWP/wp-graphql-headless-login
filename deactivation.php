<?php
/**
 * Deactivation Hook
 *
 * @package WPGraphql\Login
 * @since 0.0.1
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login;

use WPGraphQL\Login\Admin\SettingsRegistry;
use WPGraphQL\Login\Admin\Upgrade\AbstractUpgrade;

	/**
	 * Runs when WPGraphQL is de-activated.
	 *
	 * This cleans up data that WPGraphQL stores.
	 *
	 * @since 0.0.1
	 */
function deactivation_callback(): callable {
	return static function (): void {
		// Fire an action when WPGraphQL is de-activating.
		do_action( 'graphql_login_deactivate' );

		// Delete data during activation.
		delete_data();
	};
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
	);

	if ( empty( $options ) ) {
		return;
	}

	foreach ( $options as $option ) {
		delete_option( $option );
	}

	// Fire an action when data is deleted.
	do_action( 'graphql_login_delete_data' );
}
