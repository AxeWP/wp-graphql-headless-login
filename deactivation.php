<?php
/**
 * Deactivation Hook
 *
 * @package WPGraphql\Login
 * @since 0.0.1
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login;

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

	// Delete plugin version.
	delete_option( 'wp_graphql_login_version' );

	// Initialize the settings API.
	$settings = \WPGraphQL\Login\Admin\Settings::get_all_settings();

	if ( empty( $settings ) ) {
		return;
	}

	// Get all the setting keys across various groups.
	$settings = array_reduce(
		$settings,
		static function ( $carry, $item ) {
			return array_merge( $carry, array_keys( $item ) );
		},
		[]
	);

	// Loop over the registered settings fields and delete the options.
	if ( ! empty( $settings ) && is_array( $settings ) ) {
		foreach ( array_keys( $settings ) as $option_name ) {
			delete_option( $option_name );
		}
	}

	// Fire an action when data is deleted.
	do_action( 'graphql_login_delete_data' );
}
