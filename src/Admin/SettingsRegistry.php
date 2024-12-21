<?php
/**
 * The Settings Registry
 *
 * @package WPGraphQL\Login\Admin
 * @since @next-version
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Admin;

use WPGraphQL\Login\Vendor\AxeWP\GraphQL\Interfaces\Registrable;

/**
 * Class SettingsRegistry
 */
class SettingsRegistry implements Registrable {
	/**
	 * The instantiated settings.
	 *
	 * @var ?array<string,\WPGraphQL\Login\Admin\Settings\AbstractSettings>
	 */
	protected static ?array $settings;

	/**
	 * {@inheritDoc}
	 */
	public static function init(): void {
		if ( isset( self::$settings ) ) {
			return;
		}

		$classes_to_register = [
			Settings\AccessControlSettings::class,
			Settings\PluginSettings::class,
		];

		foreach ( $classes_to_register as $class ) {
			$instance = new $class();

			self::$settings[ $instance::get_slug() ] = $instance;
		}
	}

	/**
	 * Register the settings from the registry.
	 */
	public static function register_settings(): void {
		foreach ( self::get_all() as $setting ) {
			$setting->register();
		}
	}

	/**
	 * Get all the registered settings instances.
	 *
	 * @return array<string,\WPGraphQL\Login\Admin\Settings\AbstractSettings>
	 */
	public static function get_all(): array {
		if ( ! isset( self::$settings ) ) {
			self::init();
		}

		/** @var array<string,\WPGraphQL\Login\Admin\Settings\AbstractSettings> */
		return self::$settings;
	}

	/**
	 * Get a specific setting instance by slug.
	 *
	 * @param string $slug The setting slug.
	 */
	public static function get( string $slug ): ?\WPGraphQL\Login\Admin\Settings\AbstractSettings {
		if ( ! isset( self::$settings ) ) {
			self::init();
		}

		return self::$settings[ $slug ] ?? null;
	}
}
