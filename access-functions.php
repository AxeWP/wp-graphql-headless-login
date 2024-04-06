<?php
/**
 * This file contains access functions for various class methods.
 *
 * @package WPGraphQL/Login
 * @since 0.0.1
 */

declare( strict_types = 1 );

use WPGraphQL\Login\Utils\Utils;

if ( ! function_exists( 'graphql_login_get_setting' ) ) {
	/**
	 * Get an option value from the plugin settings.
	 *
	 * @param string      $option_name   The key of the option to return.
	 * @param mixed|false $default_value The default value the setting should return if no value is set.
	 *
	 * @uses \WPGraphQL\Login\Utils\Utils::get_setting()
	 *
	 * @return mixed
	 * @since 0.0.1
	 */
	function graphql_login_get_setting( string $option_name, $default_value = '' ) {
		return Utils::get_setting( $option_name, $default_value );
	}
}


if ( ! function_exists( 'graphql_login_get_provider_settings' ) ) {
	/**
	 * Gets the provider settings from the database.
	 *
	 * @param string $slug The provider slug.
	 *
	 * @uses \WPGraphQL\Login\Utils\Utils::get_provider_settings()
	 *
	 * @return array<string,mixed>
	 *
	 * @since 0.0.1
	 */
	function graphql_login_get_provider_settings( string $slug ): array {
		return Utils::get_provider_settings( $slug );
	}
}
