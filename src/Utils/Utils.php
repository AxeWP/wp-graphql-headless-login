<?php
/**
 * Utility functions.
 *
 * @package WPGraphQL/Login/Utils
 */

namespace WPGraphQL\Login\Utils;

use WPGraphQL\Login\Admin\Settings;
use WPGraphQL\Login\Auth\ProviderRegistry;

/**
 * Class - Utils
 */
class Utils {
	/**
	 * The plugin settings.
	 *
	 * @var array
	 */
	protected static array $settings = [];

	/**
	 * The providers config
	 *
	 * @var array
	 */
	protected static array $providers = [];

	/**
	 * Gets a single plugin setting.
	 *
	 * @param string      $option_name The name of the setting.
	 * @param mixed|false $default The default value. Optional. Default false.
	 *
	 * @return mixed
	 */
	public static function get_setting( string $option_name, $default = false ) {
		if ( ! isset( self::$settings[ $option_name ] ) ) {
			$value = get_option( Settings::$settings_prefix . $option_name, $default );
			/**
			 * Filter the value before returning it
			 *
			 * @param mixed  $value          The value of the field
			 * @param string $option_name    The name of the option
			 * @param mixed  $default        The default value if there is no value set
			 */
			self::$settings[ $option_name ] = apply_filters( 'graphql_login_setting', $value, $option_name, $default );
		}

		return self::$settings[ $option_name ];
	}

	/**
	 * Gets the provider settings.
	 *
	 * @param string $slug The provider slug.
	 *
	 * @return array
	 */
	public static function get_provider_settings( string $slug ) {
		if ( ! isset( self::$providers[ $slug ] ) ) {
			$settings = get_option( Settings::$provider_settings_prefix . $slug, [] );
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
	 * @return array
	 */
	public static function get_all_provider_settings() : array {
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
	public static function is_current_user( $user ) : bool {
		$user_id = $user instanceof \WP_User ? $user->ID : $user;

		if ( empty( $user_id ) ) {
			return false;
		}

		return get_current_user_id() === $user_id;
	}
}
