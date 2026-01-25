<?php

/**
 * Fix URLs for WPGraphQL requests made during Codeception tests.
 *
 * This is because WPBrowser tests run inside Docker and use different hostnames/ports,
 * but the Codeception tests are expecting the outside-Docker URLs.
 *
 * @param string $url The URL to potentially fix.
 * @return string The fixed URL.
 */
function wpgraphql_wpenv_fix_url( $url ) {
	// Bail if not a Codeception test.
	if ( empty( $_SERVER['HTTP_X_TEST_REQUEST'] ) && empty( $_SERVER['HTTP_X_WPBROWSER_REQUEST'] ) ) {
		return $url;
	}

	return str_replace(
		[
			'http://localhost:8889',
			'http://localhost',
			'http://localhost',
		],
		'http://tests-wordpress',
		$url
	);
}

add_filter( 'site_url', 'wpgraphql_wpenv_fix_url', 1 );
add_filter( 'home_url', 'wpgraphql_wpenv_fix_url', 1 );
add_filter( 'wp_login_url', 'wpgraphql_wpenv_fix_url', 1 );
add_filter( 'admin_url', 'wpgraphql_wpenv_fix_url', 1 );
add_filter( 'test_cookie', 'wpgraphql_wpenv_fix_url', 1 );
add_filter( 'cookie_domain', 'wpgraphql_wpenv_fix_url', 1 );

/**
 * Normalize cookie domains for Codeception tests.
 *
 * This ensures cookies work correctly when tests run inside Docker
 * but expect consistent domain values.
 *
 * @param string[] $domains The cookie domain.
 * @return string[]
 */
add_filter(
	'graphql_login_iss_allowed_domains',
	static function ( $domains ) {
		// Only apply for Codeception tests.
		if ( empty( $_SERVER['HTTP_X_TEST_REQUEST'] ) && empty( $_SERVER['HTTP_X_WPBROWSER_REQUEST'] ) ) {
			return $domains;
		}

		// Normalize both localhost:8889 and tests-wordpress to tests-wordpress.
		return str_replace(
			[
				'localhost:8889',
				'localhost',
			],
			'tests-wordpress',
			$domains
		);
	}
);

add_filter(
	'graphql_login_token_before_sign',
	static function ( $token ) {

		$token['iss'] = str_replace(
			[
				'localhost:8889',
				'localhost',
			],
			'tests-wordpress',
			$token['iss']
		);

		return $token;
	}
);

add_filter(
	'graphql_login_cookie_setting',
	static function ( $setting ) {
		// Bail if not a Codeception test.
		if ( empty( $_SERVER['HTTP_X_TEST_REQUEST'] ) && empty( $_SERVER['HTTP_X_WPBROWSER_REQUEST'] ) ) {
			return $setting;
		}

		// Map cookieDomain from localhost to tests-wordpress.
		if ( ! empty( $setting['cookieDomain'] ) ) {
			$setting['cookieDomain'] = str_replace(
				[
					'localhost:8889',
					'localhost',
				],
				'tests-wordpress',
				$setting['cookieDomain']
			);
		}

		return $setting;
	}
);

/**
 * Fix WPGraphQL viewer field returning null in Codeception tests.
 *
 * Issue: WPLoader calls wp_get_current_user() during test setup (before cookie auth runs),
 * caching user ID 0. When WPGraphQL resolves the viewer field, it gets the cached value
 * instead of the authenticated user.
 *
 * Solution: On init_graphql_request, if a login cookie is present but no current user is set,
 * re-validate the cookie and set the current user.
 */
add_action(
	'init_graphql_request',
	static function () {
		// Bail if not a Codeception test.
		if ( empty( $_SERVER['HTTP_X_TEST_REQUEST'] ) && empty( $_SERVER['HTTP_X_WPBROWSER_REQUEST'] ) ) {
			return;
		}

		// Only proceed if no current user is set.
		if ( get_current_user_id() ) {
			return;
		}

		// Check for WordPress login cookie and re-validate if present.
		$cookies = $_COOKIE;
		foreach ( $cookies as $cookie_name => $cookie_value ) {
			if ( strpos( $cookie_name, 'wordpress_logged_in_' ) === 0 ) {
				$user_id = wp_validate_auth_cookie( $cookie_value, 'logged_in' );
				if ( $user_id ) {
					wp_set_current_user( $user_id );
				}
				break;
			}
		}
	},
	1
);
