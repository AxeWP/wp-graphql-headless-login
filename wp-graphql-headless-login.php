<?php
/**
 * Plugin Name: Headless Login for WPGraphQL
 * Plugin URI: https://github.com/AxeWP/wp-graphql-headless-login
 * GitHub Plugin URI: https://github.com/AxeWP/wp-graphql-headless-login
 * Description: A WordPress plugin for headless authentication and login with WPGraphQL.
 * Author: AxePress
 * Author URI: https://github.com/AxeWP
 * Update URI: https://github.com/AxeWP/wp-graphql-headless-login
 * x-release-please-start-version
 * Version: 0.4.4
 * x-release-please-end
 * Text Domain: wp-graphql-headless-login
 * Requires at least: 6.9
 * Requires PHP: 8.2
 * Requires Plugins: wp-graphql
 * WPGraphQL requires at least: 2.14.1
 * WPGraphQL tested up to: 2.23.1
 * License: GPL-3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package WPGraphQL\Login
 * @author axepress
 * @license GPL-3
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Define plugin constants.
 *
 * @since 0.0.1
 */
function constants(): void {
	// Plugin version.
	if ( ! defined( 'WPGRAPHQL_LOGIN_VERSION' ) ) {
		define( 'WPGRAPHQL_LOGIN_VERSION', '0.4.4' ); // x-release-please-version.
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
}

constants();

// Load the autoloader.
require_once __DIR__ . '/src/Autoloader.php';
if ( ! \WPGraphQL\Login\Autoloader::autoload() ) {
	return;
}

// Load the main plugin class.
if ( class_exists( 'WPGraphQL\Login\Main' ) ) {
	add_action(
		'plugins_loaded',
		static function () {
			\WPGraphQL\Login\Main::get_instance();
		}
	);
}
