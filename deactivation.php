<?php
/**
 * Deactivation Hook
 *
 * @package WPGraphql\Login
 * @since 0.0.1
 */

/**
 * Runs when WPGraphQL is de-activated.
 *
 * This cleans up data that WPGraphQL stores.
 *
 * @since 0.0.1
 */
function graphql_login_deactivation_callback() : callable {
	return static function () : void {
		// Fire an action when WPGraphQL is de-activating.
		do_action( 'graphql_login_deactivate' );

		// Delete data during activation.
		graphql_login_delete_data();
	};
}

/**
 * Delete data on deactivation.
 *
 * @since 0.0.1
 */
function graphql_login_delete_data() : void {

	// Check if the plugin is set to delete data or not.
	$delete_data = graphql_login_get_setting( 'delete_data_on_deactivate' );

	// Bail if not set to delete.
	if ( empty( $delete_data ) ) {
		return;
	}

	// Delete plugin version.
	delete_option( 'wp_graphql_login_version' );

	// Initialize the settings API.
	$settings = WPGraphQL\Login\Admin\Settings::get_settings_config();

	// Loop over the registered settings fields and delete the options.
	if ( ! empty( $settings ) && is_array( $settings ) ) {
		foreach ( array_keys( $settings ) as $option_name ) {
			delete_option( $option_name );
		}
	}

	// Fire an action when data is deleted.
	do_action( 'graphql_login_delete_data' );
}
