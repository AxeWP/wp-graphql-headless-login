<?php
/**
 * Utility functions.
 *
 * @package WPGraphQL/Login/Utils
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Utils;

use WPGraphQL\Login\Admin\Settings\AccessControlSettings;
use WPGraphQL\Login\Admin\Settings\PluginSettings;
use WPGraphQL\Login\Admin\Settings\ProviderSettings;
use WPGraphQL\Login\Auth\ProviderRegistry;

/**
 * Class - Utils
 */
class Utils {
	/**
	 * The plugin settings.
	 *
	 * @var array<string,mixed>
	 */
	protected static array $settings = [];

	/**
	 * The providers config
	 *
	 * @var array<string,array<string,mixed>>
	 */
	protected static array $providers = [];

	/**
	 * The Access Control Settings
	 *
	 * @var ?array<string,mixed>
	 */
	protected static $access_control;

	/**
	 * Gets a single plugin setting.
	 *
	 * Uses get_option() which means scalars are converted into strings.
	 *
	 * @see https://developer.wordpress.org/reference/functions/get_option/#return
	 *
	 * @param string      $option_name   The name of the setting.
	 * @param mixed|false $default_value The default value. Optional. Default false.
	 *
	 * @return mixed
	 */
	public static function get_setting( string $option_name, $default_value = false ) {
		if ( ! isset( self::$settings[ $option_name ] ) ) {
			$value = get_option( PluginSettings::$settings_prefix . $option_name, $default_value );
			/**
			 * Filter the value before returning it
			 *
			 * @param mixed  $value         The value of the field
			 * @param string $option_name   The name of the option
			 * @param mixed  $default_value The default value if there is no value set
			 */
			self::$settings[ $option_name ] = apply_filters( 'graphql_login_setting', $value, $option_name, $default_value );
		}

		return self::$settings[ $option_name ];
	}

	/**
	 * Updates a single plugin setting.
	 * This is a wrapper for update_option() and updates the internal cache.
	 *
	 * @param string $option_name The name of the setting.
	 * @param mixed  $value The value of the setting.
	 */
	public static function update_plugin_setting( string $option_name, $value ): bool {
		$option_name = PluginSettings::$settings_prefix . $option_name;

		$success = update_option( $option_name, $value );

		if ( $success ) {
			self::$settings[ $option_name ] = $value;
		}

		return $success;
	}

	/**
	 * Gets a single access control setting.
	 *
	 * Uses get_option() which means scalars are converted into strings.
	 *
	 * @see https://developer.wordpress.org/reference/functions/get_option/#return
	 *
	 * @param string      $option_name   The name of the setting.
	 * @param mixed|false $default_value The default value. Optional. Default false.
	 *
	 * @return mixed
	 */
	public static function get_access_control_setting( string $option_name, $default_value = false ) {
		if ( ! isset( self::$access_control ) ) {
			$access_control = get_option( AccessControlSettings::$settings_prefix . 'access_control' );

			/**
			 * Filter the value before returning it
			 *
			 * @param mixed  $value          The value of the field
			 * @param mixed  $default        The default value if there is no value set
			 */
			self::$access_control = apply_filters( 'graphql_login_access_control_settings', $access_control, $default_value );
		}

		return isset( self::$access_control[ $option_name ] ) ? self::$access_control[ $option_name ] : $default_value;
	}

	/**
	 * Gets the provider settings.
	 *
	 * @param string $slug The provider slug.
	 *
	 * @return array<string,mixed>
	 */
	public static function get_provider_settings( string $slug ) {
		if ( ! isset( self::$providers[ $slug ] ) ) {
			$settings = get_option( ProviderSettings::$settings_prefix . $slug, [] );

			/**
			 * Filter the provider settings before returning it
			 *
			 * @param array  $settings       The provider settings.
			 * @param string $slug           The provider slug.
			 */
			self::$providers[ $slug ] = apply_filters( 'graphql_login_provider_settings', $settings, $slug );
		}

		return self::$providers[ $slug ];
	}

	/**
	 * Gets all provider settings from the database.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function get_all_provider_settings(): array {
		$providers = ProviderRegistry::get_instance()->get_registered_providers();

		foreach ( array_keys( $providers ) as $slug ) {
			if ( ! isset( self::$providers[ $slug ] ) ) {
				self::get_provider_settings( $slug );
			}
		}

		return self::$providers;
	}

	/**
	 * Checks whether the provided user is currently logged in.
	 *
	 * @param int|\WP_User $user The user or user ID.
	 */
	public static function is_current_user( $user ): bool {
		$user_id = $user instanceof \WP_User ? $user->ID : $user;

		if ( empty( $user_id ) ) {
			return false;
		}

		return get_current_user_id() === $user_id;
	}
}
