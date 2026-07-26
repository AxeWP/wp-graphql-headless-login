<?php
/**
 * Bootstrap the PHPUnit tests.
 *
 * @package Tests\WPGraphQL\Login
 *
 * phpcs:disable WordPressVIPMinimum.Files.IncludingFile.UsingVariable
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals
 */

declare( strict_types = 1 );

define( 'TESTS_REPO_ROOT_DIR', dirname( __DIR__, 2 ) );

// Load Composer dependencies if applicable.
if ( file_exists( TESTS_REPO_ROOT_DIR . '/vendor/autoload.php' ) ) {
	require_once TESTS_REPO_ROOT_DIR . '/vendor/autoload.php';
}

// Detect where to load the WordPress tests environment from.
if ( false !== getenv( 'WP_TESTS_DIR' ) ) {
	$_test_root = getenv( 'WP_TESTS_DIR' );
} elseif ( false !== getenv( 'WP_DEVELOP_DIR' ) ) {
	$_test_root = getenv( 'WP_DEVELOP_DIR' ) . '/tests/phpunit';
} elseif ( false !== getenv( 'WP_PHPUNIT__DIR' ) ) {
	$_test_root = getenv( 'WP_PHPUNIT__DIR' );
} elseif ( file_exists( TESTS_REPO_ROOT_DIR . '/../../../../../tests/phpunit/includes/functions.php' ) ) {
	$_test_root = TESTS_REPO_ROOT_DIR . '/../../../../../tests/phpunit';
} else { // Fallback.
	$_test_root = '/tmp/wordpress-tests-lib';
}

// Give access to tests_add_filter() function.
require_once $_test_root . '/includes/functions.php';

tests_add_filter( 'graphql_debug_enabled', '__return_true', 99 );

// Activate WPGraphQL and the plugin.
tests_add_filter(
	'muplugins_loaded',
	static function (): void {
		// Require ( to bypass require_once ).
		require WP_CONTENT_DIR . '/plugins/wp-graphql/wp-graphql.php';
		require TESTS_REPO_ROOT_DIR . '/wp-graphql-headless-login.php';
	}
);

// Start up the WP testing environment.
require $_test_root . '/includes/bootstrap.php';

// WPGraphQL registers `graphql_general_settings` without a `default` arg, so we need to seed it.
update_option( 'graphql_general_settings', [] );
