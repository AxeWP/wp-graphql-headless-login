<?php
/**
 * Initializes a singleton instance of the plugin.
 *
 * @package WPGraphQL\Login
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login;

use WPGraphQL\Login\Admin\Settings;
use WPGraphQL\Login\Admin\UserProfile;
use WPGraphQL\Login\Auth\ProviderRegistry;
use WPGraphQL\Login\Vendor\AxeWP\GraphQL\Helper\Helper;

if ( ! class_exists( \WPGraphQL\Login\Main::class ) ) :

	/**
	 * Class - Main
	 */
	final class Main {
		/**
		 * Class instances.
		 *
		 * @var ?self $instance
		 */
		private static $instance;

		/**
		 * Constructor
		 */
		public static function instance(): self {
			if ( ! isset( self::$instance ) ) {
				// You cant test a singleton.
				// @codeCoverageIgnoreStart .

				self::$instance = new self();
				self::$instance->setup();
				// @codeCoverageIgnoreEnd
			}

			/**
			 * Fire off init action.
			 *
			 * @param self $instance the instance of the plugin class.
			 */
			do_action( 'graphql_login_init', self::$instance );

			return self::$instance;
		}

		/**
		 * Sets up the schema.
		 *
		 * @codeCoverageIgnore
		 */
		private function setup(): void {
			// Setup boilerplate hook prefix.
			Helper::set_hook_prefix( 'graphql_login' );

			// Setup plugin.
			CoreSchemaFilters::init();
			WoocommerceSchemaFilters::init();
			Settings::init();
			UserProfile::init();
			ProviderRegistry::get_instance();

			// Initialize plugin type registry.
			add_action( get_graphql_register_action(), [ TypeRegistry::class, 'init' ] );
		}

		/**
		 * Throw error on object clone.
		 * The whole idea of the singleton design pattern is that there is a single object
		 * therefore, we don't want the object to be cloned.
		 *
		 * @codeCoverageIgnore
		 *
		 * @return void
		 */
		public function __clone() {
			// Cloning instances of the class is forbidden.
			_doing_it_wrong( __FUNCTION__, esc_html__( 'The plugin Main class should not be cloned.', 'wp-graphql-headless-login' ), '0.0.1' );
		}

		/**
		 * Disable unserializing of the class.
		 *
		 * @codeCoverageIgnore
		 */
		public function __wakeup(): void {
			// De-serializing instances of the class is forbidden.
			_doing_it_wrong( __FUNCTION__, esc_html__( 'De-serializing instances of the plugin Main class is not allowed.', 'wp-graphql-headless-login' ), '0.0.1' );
		}
	}
endif;
