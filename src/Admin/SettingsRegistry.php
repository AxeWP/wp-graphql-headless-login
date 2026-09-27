<?php
/**
 * The Settings Registry
 *
 * @package WPGraphQL\Login\Admin
 * @since 0.4.0
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Admin;

use WPGraphQL\Login\Vendor\AxeWP\Common\Contracts\Interfaces\Registrable;

/**
 * Class SettingsRegistry
 */
final class SettingsRegistry implements Registrable {
	/**
	 * The instantiated settings.
	 *
	 * @var array<string,\WPGraphQL\Login\Admin\Settings\AbstractSettings>
	 */
	private static array $settings = [];

	/**
	 * {@inheritDoc}
	 */
	public function register_hooks(): void {
		/**
		 * Register settings _after_ WPGraphQL initializes.
		 *
		 * Prevents conflict caused by
		 *
		 * @see https://github.com/wp-graphql/wp-graphql/pull/3878
		 */
		add_action( 'init', [ self::class, 'register_settings' ], 12 );
	}

	/**
	 * Instantiates the settings classes for the registry.
	 */
	public static function init(): void {
		if ( ! empty( self::$settings ) ) {
			return;
		}

		$classes_to_register = [
			Settings\AccessControlSettings::class,
			Settings\CookieSettings::class,
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
		self::init();

		return self::$settings;
	}

	/**
	 * Get a specific setting instance by slug.
	 *
	 * @param string $slug The setting slug.
	 */
	public static function get( string $slug ): ?\WPGraphQL\Login\Admin\Settings\AbstractSettings {
		self::init();

		return self::$settings[ $slug ] ?? null;
	}
}
