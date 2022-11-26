<?php
/**
 * Plugin Name: Headless Login for WPGraphQL
 * Plugin URI: https://github.com/AxeWP/wp-graphql-headless-login
 * GitHub Plugin URI: https://github.com/AxeWP/wp-graphql-headless-login
 * Description: A WordPress plugin for headless authentication and login with WPGraphQL.
 * Author: AxePress
 * Author URI: https://github.com/AxeWP
 * Update URI: https://github.com/AxeWP/wp-graphql-headless-login
 * Version: 0.0.1
 * Text Domain: wp-graphql-headless-login
 * Domain Path: /languages
 * Requires at least: 5.6
 * Tested up to: 6.1.1
 * Requires PHP: 7.4
 * WPGraphQL requires at least: 1.12.0
 * License: GPL-3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package WPGraphQL\Login
 * @author axepress
 * @license GPL-3
 * @version 0.0.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// If the codeception remote coverage file exists, require it.
// This file should only exist locally or when CI bootstraps the environment for testing.
if ( file_exists( __DIR__ . '/c3.php' ) ) {
	require_once __DIR__ . '/c3.php';
}

// Run this function when the plugin is activated.
if ( file_exists( __DIR__ . '/activation.php' ) ) {
	require_once __DIR__ . '/activation.php';
	register_activation_hook( __FILE__, 'graphql_login_activation_callback' );
}

// Run this function when the plugin is deactivated.
if ( file_exists( __DIR__ . '/deactivation.php' ) ) {
	require_once __DIR__ . '/deactivation.php';
	register_activation_hook( __FILE__, 'graphql_login_deactivation_callback' );
}


/**
 * Define plugin constants.
 *
 * @since 0.0.1
 */
function graphql_login_constants() : void {
	// Plugin version.
	if ( ! defined( 'WPGRAPHQL_LOGIN_VERSION' ) ) {
		define( 'WPGRAPHQL_LOGIN_VERSION', '0.0.1' );
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

	// The Plugin Boilerplate hook prefix.
	if ( ! defined( 'AXEWP_PB_HOOK_PREFIX' ) ) {
		define( 'AXEWP_PB_HOOK_PREFIX', 'graphql_login' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
	}
}

/**
 * Checks if all the the required plugins are installed and activated.
 *
 * @todo check specific version.
 *
 * @since 0.0.1
 */
function graphql_login_dependencies_not_ready() : array {
	$deps = [];

	if ( ! class_exists( '\WPGraphQL' ) ) {
		$deps[] = 'WPGraphQL';
	}

	return $deps;
}

/**
 * Initializes plugin.
 *
 * @since 0.0.1
 */
function graphql_login_init() : void {
	graphql_login_constants();

	// Get the dependencies that are not ready.
	$not_ready = graphql_login_dependencies_not_ready();

	// Load our plugin and initialize.
	if ( empty( $not_ready ) && defined( 'WPGRAPHQL_LOGIN_PLUGIN_DIR' ) ) {
		require_once WPGRAPHQL_LOGIN_PLUGIN_DIR . 'src/Main.php';
		\WPGraphQL\Login\Main::instance();
	}

	// Output an error notice.
	foreach ( $not_ready as $dep ) {
		add_action(
			'admin_notices',
			static function () use ( $dep ): void {
				?>
				<div class="error notice">
					<p>
						<?php
						printf(
								/* translators: dependency not ready error message */
							esc_html__( '%1$s must be active for Headless Login for WPGraphQL to work.', 'wp-graphql-headless-login' ),
							esc_html( $dep )
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
add_action( 'graphql_init', 'graphql_login_init' );
