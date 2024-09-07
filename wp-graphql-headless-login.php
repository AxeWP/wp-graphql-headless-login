<?php
/**
 * Plugin Name: Headless Login for WPGraphQL
 * Plugin URI: https://github.com/AxeWP/wp-graphql-headless-login
 * GitHub Plugin URI: https://github.com/AxeWP/wp-graphql-headless-login
 * Description: A WordPress plugin for headless authentication and login with WPGraphQL.
 * Author: AxePress
 * Author URI: https://github.com/AxeWP
 * Update URI: https://github.com/AxeWP/wp-graphql-headless-login
 * Version: 0.3.1
 * Text Domain: wp-graphql-headless-login
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to: 6.6
 * Requires PHP: 7.4
 * Requires Plugins: wp-graphql
 * WPGraphQL requires at least: 1.14.0
 * License: GPL-3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package WPGraphQL\Login
 * @author axepress
 * @license GPL-3
 * @version 0.3.1
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// If the codeception remote coverage file exists, require it.
// This file should only exist locally or when CI bootstraps the environment for testing.
if ( file_exists( __DIR__ . '/c3.php' ) ) {
	require_once __DIR__ . '/c3.php';
}

// Load the autoloader.
require_once __DIR__ . '/src/Autoloader.php';
if ( ! \WPGraphQL\Login\Autoloader::autoload() ) {
	return;
}

// Run this function when the plugin is activated.
if ( file_exists( __DIR__ . '/activation.php' ) ) {
	require_once __DIR__ . '/activation.php';
	register_activation_hook( __FILE__, 'WPGraphQL\Login\activation_callback' );
}

// Run this function when the plugin is deactivated.
if ( file_exists( __DIR__ . '/deactivation.php' ) ) {
	require_once __DIR__ . '/deactivation.php';
	register_activation_hook( __FILE__, 'WPGraphQL\Login\deactivation_callback' );
}


	/**
	 * Define plugin constants.
	 *
	 * @since 0.0.1
	 */
function constants(): void {
	// Plugin version.
	if ( ! defined( 'WPGRAPHQL_LOGIN_VERSION' ) ) {
		define( 'WPGRAPHQL_LOGIN_VERSION', '0.3.1' );
	}

	// Plugin Folder Path.
	if ( ! defined( 'WPGRAPHQL_LOGIN_PLUGIN_DIR' ) ) {
		define( 'WPGRAPHQL_LOGIN_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
	}

	// Plugin Folder URL.
	if ( ! defined( 'WPGRAPHQL_LOGIN_PLUGIN_URL' ) ) {
		define( 'WPGRAPHQL_LOGIN_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
	}

	// Plugin Root File.
	if ( ! defined( 'WPGRAPHQL_LOGIN_PLUGIN_FILE' ) ) {
		define( 'WPGRAPHQL_LOGIN_PLUGIN_FILE', __FILE__ );
	}

	// Whether to autoload the files or not.
	if ( ! defined( 'WPGRAPHQL_LOGIN_AUTOLOAD' ) ) {
		define( 'WPGRAPHQL_LOGIN_AUTOLOAD', true );
	}
}

/**
 * Checks if all the the required plugins are installed and activated.
 *
 * @since 0.0.1
 *
 * @return array<string,string>
 */
function dependencies_not_ready(): array {
	$wpgraphql_version = '1.14.0';

	$deps = [];

	if ( ! class_exists( 'WPGraphQL' ) || ( defined( 'WPGRAPHQL_VERSION' ) && version_compare( WPGRAPHQL_VERSION, $wpgraphql_version, '<' ) ) ) {
		$deps['WPGraphQL'] = $wpgraphql_version;
	}

	return $deps;
}

	/**
	 * Checks if any known plugin conflicts are present.
	 *
	 * @since 0.0.4
	 *
	 * @return string[]
	 */
function plugin_conflicts(): array {
	$conflicts = [];

	if ( class_exists( 'WPGraphQL\JWT_Authentication\JWT_Authentication' ) && is_plugin_active( 'wp-graphql-jwt-authentication/wp-graphql-jwt-authentication.php' ) ) {
		$conflicts[] = 'WPGraphQL JWT Authentication';
	}

	if ( class_exists( 'WP_GraphQL_CORS' ) && is_plugin_active( 'wp-graphql-cors/wp-graphql-cors.php' ) ) {
		$conflicts[] = 'WPGraphQL CORS';
	}

	return $conflicts;
}

	/**
	 * Initializes plugin.
	 *
	 * @since 0.0.1
	 */
function init(): void {
	constants();

	// Get the dependencies that are not ready.
	$not_ready = dependencies_not_ready();

	// Get the conflicting plugins.
	$conflicts = plugin_conflicts();

	// Load our plugin and initialize.
	if ( empty( $not_ready ) && empty( $conflicts ) && defined( 'WPGRAPHQL_LOGIN_PLUGIN_DIR' ) ) {
		require_once WPGRAPHQL_LOGIN_PLUGIN_DIR . 'src/Main.php';
		\WPGraphQL\Login\Main::instance();
	}

	// Output an error notice for the dependencies that are not ready.
	foreach ( $not_ready as $dep => $version ) {
		add_action(
			'admin_notices',
			static function () use ( $dep, $version ) {
				?>
				<div class="error notice">
					<p>
					<?php
					printf(
						/* translators: dependency not ready error message */
						esc_html__( '%1$s (v%2$s) must be active for Headless Login for WPGraphqL to work.', 'wp-graphql-headless-login' ),
						esc_attr( $dep ),
						esc_attr( $version ),
					);
					?>
					</p>
				</div>
					<?php
			}
		);
	}

	// Output an error notice for the conflicting plugins.
	foreach ( $conflicts as $conflict ) {
		add_action(
			'admin_notices',
			static function () use ( $conflict ) {
				?>
				<div class="error notice">
					<p>
					<?php
					printf(
						/* translators: dependency not ready error message */
						esc_html__( '%1$s is not compatible with Headless Login for WPGraphqL. Please deactivate it.', 'wp-graphql-headless-login' ),
						esc_attr( $conflict ),
					);
					?>
					</p>
				</div>
					<?php
			}
		);
	}
}

// Initialize the plugin.
add_action( 'graphql_init', 'WPGraphQL\Login\init' );

// Some plugins may rely on authentication even before our plugin is initialized.
if ( class_exists( 'WPGraphQL\Login\Auth\ServerAuthentication' ) ) {
	\WPGraphQL\Login\Auth\ServerAuthentication::init();
}
