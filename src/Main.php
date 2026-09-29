<?php
/**
 * Initializes a singleton instance of the plugin.
 *
 * @package WPGraphQL\Login
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login;

use WPGraphQL\Login\Admin;
use WPGraphQL\Login\Vendor\AxeWP\Common\Contracts\Traits\Singleton;
use WPGraphQL\Login\Vendor\AxeWP\Common\Core\Config;

/**
 * Class - Main
 */
final class Main {
	use Singleton {
		get_instance as private trait_instance;
	}

	/**
	 * Registrable classes are entrypoints that "hook" into WordPress.
	 *
	 * @var class-string<\WPGraphQL\Login\Vendor\AxeWP\Common\Contracts\Interfaces\Registrable>[]
	 */
	private const REGISTRABLE_CLASSES = [
		GraphQL\CoreSchemaFilters::class,
		GraphQL\TypeRegistry::class,
		GraphQL\Model\User::class,
		Extensions\WooCommerce::class,
		Auth\ServerAuthentication::class,
		Settings\SettingsRegistry::class,
		Admin\Admin::class,
		Admin\Upgrade\UpgradeRegistry::class,
		Admin\UserProfile::class,
	];

	/**
	 * Called when the singleton instance is created.
	 */
	protected function __construct() {
		$this->setup();
	}

	/**
	 * Get the singleton instance of the plugin.
	 */
	public static function get_instance(): self {
		$instance = self::trait_instance();

		/**
		 * Fire off init action.
		 *
		 * @param self $instance the instance of the plugin class.
		 */
		do_action( 'graphql_login_init', $instance );

		return $instance;
	}

	/**
	 * Sets up the schema.
	 *
	 * @codeCoverageIgnore
	 */
	private function setup(): void {
		// Set up the axewp-common hook prefix.
		Config::set_hook_prefix( 'graphql_login' );

		// Setup plugin.
		add_action( 'plugins_loaded', [ $this, 'load' ] );

		// Register activation and deactivation hooks.
		register_activation_hook( WPGRAPHQL_LOGIN_PLUGIN_FILE, [ $this, 'on_activation' ] );
		register_deactivation_hook( WPGRAPHQL_LOGIN_PLUGIN_FILE, [ $this, 'on_deactivation' ] );
	}

	/**
	 * Load the plugin classes.
	 */
	public function load(): void {
		// Only load plugin classes if all dependencies are met.
		if ( ! Dependencies::check() ) {
			return;
		}

		// Loop through all the classes, instantiate them, and register any hooks.
		foreach ( self::REGISTRABLE_CLASSES as $class_name ) {
			$instance = new $class_name();

			$instance->register_hooks();
		}

		// Do other generalizable stuff here.
	}

	/**
	 * Call the activation callback.
	 *
	 * @internal
	 */
	public function on_activation(): void {
		activation_callback();
	}

	/**
	 * Call the deactivation callback.
	 *
	 * @internal
	 */
	public function on_deactivation(): void {
		deactivation_callback();
	}
}
