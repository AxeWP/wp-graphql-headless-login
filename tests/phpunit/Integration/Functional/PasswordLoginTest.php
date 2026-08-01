<?php
/**
 * Tests the Password provider login flow end-to-end.
 *
 * @package Tests\WPGraphQL\Login\Integration\Functional
 */

namespace Tests\WPGraphQL\Login\Integration\Functional;

use Tests\WPGraphQL\Login\TestCase;
use WPGraphQL\Login\Admin\Settings\AccessControlSettings;
use WPGraphQL\Login\Admin\Settings\CookieSettings;
use WPGraphQL\Login\Admin\Settings\ProviderSettings;

/**
 * Tests logging in and out with the Password provider.
 */
class PasswordLoginTest extends TestCase {
	/**
	 * {@inheritDoc}
	 */
	public function setUp(): void {
		parent::setUp();

		$this->set_client_config( 'password', $this->get_password_provider_config( true ) );

		update_option(
			AccessControlSettings::get_slug(),
			[
				'shouldBlockUnauthorizedDomains' => true,
				'hasSiteAddressInOrigin'         => true,
				'additionalAuthorizedDomains'    => [ 'https://example.com' ],
			]
		);

		update_option(
			CookieSettings::get_slug(),
			[
				'hasLogoutMutation'                => true,
				'hasAccessControlAllowCredentials' => true,
			]
		);

		update_option( 'graphql_general_settings', [ 'debug_mode_enabled' => 'on' ] );
		$this->reset_utils_properties();
		$this->clearSchema();
	}

	/**
	 * {@inheritDoc}
	 */
	public function tearDown(): void {
		unset( $_SERVER['HTTP_AUTHORIZATION'], $_SERVER['HTTP_ORIGIN'] );
		delete_option( ProviderSettings::$settings_prefix . 'password' );
		delete_option( AccessControlSettings::get_slug() );
		delete_option( CookieSettings::get_slug() );
		$this->reset_utils_properties();
		$this->clearSchema();

		parent::tearDown();
	}

	/**
	 * Tests that a cookie login sets the auth cookies and that logout clears the viewer.
	 */
	public function test_cookie_auth_login_sets_cookie_hooks_and_logout_clears_viewer(): void {
		// Prevent actual setcookie() calls (e.g. from wp_clear_auth_cookie() on
		// logout), which warn about already-sent headers in CLI. The cookie
		// hooks this test asserts on fire before the send_auth_cookies gate.
		// Priority 5 so capture_auth_cookie_events()'s cleanup (priority 10)
		// doesn't remove it mid-test.
		add_filter( 'send_auth_cookies', '__return_false', 5 );

		$user_id = $this->factory()->user->create(
			[
				'role'       => 'administrator',
				'user_login' => 'testuser',
				'user_pass'  => 'testpass',
			]
		);

		$this->generate_user_tokens( $user_id );
		$_SERVER['HTTP_ORIGIN'] = site_url();

		$query     = $this->get_login_mutation();
		$variables = [
			'username' => 'testuser',
			'password' => 'testpass',
		];

		$execution = $this->capture_auth_cookie_events(
			function () use ( $query, $variables ) {
				return $this->graphql( compact( 'query', 'variables' ) );
			}
		);

		$response = $execution['response'];
		$events   = $execution['events'];

		$this->assertArrayNotHasKey( 'errors', $response );
		$this->assertSame( [], $this->get_debug_messages( $response ) );
		$this->assertSame( $user_id, $response['data']['login']['user']['databaseId'] );
		$this->assertSame( 'testuser', $response['data']['login']['user']['username'] );
		$this->assertNotEmpty( $events['set_auth_cookie'] );
		$this->assertNotEmpty( $events['set_logged_in_cookie'] );

		$this->reset_utils_properties();
		$query    = $this->get_viewer_query();
		$response = $this->graphql( compact( 'query' ) );

		$this->assertArrayNotHasKey( 'errors', $response );
		$this->assertSame( $user_id, $response['data']['viewer']['databaseId'] );
		$this->assertSame( 'testuser', $response['data']['viewer']['username'] );
		$this->assertNotEmpty( $response['data']['viewer']['auth']['authToken'] );
		$this->assertNotEmpty( $response['data']['viewer']['auth']['authTokenExpiration'] );

		$query    = $this->get_logout_mutation();
		$response = $this->graphql( compact( 'query' ) );

		$this->assertArrayNotHasKey( 'errors', $response );
		$this->assertTrue( $response['data']['logout']['success'] );

		$this->reset_utils_properties();
		$query    = $this->get_viewer_query();
		$response = $this->graphql( compact( 'query' ) );

		$this->assertArrayNotHasKey( 'errors', $response );
		$this->assertNull( $response['data']['viewer'] );
	}

	/**
	 * Tests that a token login returns tokens without setting an auth cookie.
	 */
	public function test_token_auth_login_returns_tokens_without_auth_cookie(): void {
		$this->set_client_config( 'password', $this->get_password_provider_config( false ) );

		$user_id = $this->factory()->user->create(
			[
				'role'       => 'administrator',
				'user_login' => 'testuser',
				'user_pass'  => 'testpass',
			]
		);

		$this->generate_user_tokens( $user_id );
		$_SERVER['HTTP_ORIGIN'] = site_url();

		$query     = $this->get_login_mutation();
		$variables = [
			'username' => 'testuser',
			'password' => 'testpass',
		];

		$login = $this->capture_auth_cookie_events(
			function () use ( $query, $variables ) {
				return $this->graphql( compact( 'query', 'variables' ) );
			}
		);

		$this->assertArrayNotHasKey( 'errors', $login['response'] );
		$this->assertEmpty( $login['events']['set_auth_cookie'] );
		$this->assertEmpty( $login['events']['set_logged_in_cookie'] );
		$this->assertNotEmpty( $login['response']['data']['login']['authToken'] );
		wp_set_current_user( 0 );

		$query  = $this->get_viewer_query();
		$viewer = $this->graphql( compact( 'query' ) );

		$this->assertArrayNotHasKey( 'errors', $viewer );
		$this->assertNull( $viewer['data']['viewer'] );

		$logout = $this->graphql( [ 'query' => $this->get_logout_mutation() ] );
		$this->assertArrayNotHasKey( 'errors', $logout );
		$this->assertNull( $logout['data']['logout']['success'] );
	}

	/**
	 * The `login` mutation for the Password provider.
	 */
	private function get_login_mutation(): string {
		return '
			mutation LoginWithPassword( $username: String!, $password: String! ) {
				login( input: { credentials: { username: $username, password: $password }, provider: PASSWORD } ) {
					authToken
					authTokenExpiration
					refreshToken
					refreshTokenExpiration
					user {
						auth {
							isUserSecretRevoked
							linkedIdentities {
								id
								provider
							}
							userSecret
						}
						databaseId
						username
					}
				}
			}
		';
	}

	/**
	 * The Password provider config, with the authentication cookie on or off.
	 */
	private function get_password_provider_config( bool $use_authentication_cookie ): array {
		return [
			'name'          => 'Password',
			'slug'          => 'password',
			'order'         => 0,
			'isEnabled'     => true,
			'clientOptions' => [],
			'loginOptions'  => [
				'useAuthenticationCookie' => $use_authentication_cookie,
			],
		];
	}

	/**
	 * The `logout` mutation.
	 */
	private function get_logout_mutation(): string {
		return '
			mutation Logout {
				logout( input: {} ) {
					success
				}
			}
		';
	}

	/**
	 * The `viewer` query.
	 */
	private function get_viewer_query(): string {
		return '
			query {
				viewer {
					databaseId
					username
					auth {
						authToken
						authTokenExpiration
						refreshToken
						refreshTokenExpiration
						isUserSecretRevoked
						userSecret
					}
				}
			}
		';
	}

	/**
	 * Runs the callback, returning its response along with the auth cookie events it fired.
	 */
	private function capture_auth_cookie_events( callable $callback ): array {
		$events           = [
			'set_auth_cookie'      => [],
			'set_logged_in_cookie' => [],
		];
		$auth_cookie_hook = static function ( ...$args ) use ( &$events ): void {
			$events['set_auth_cookie'][] = $args;
		};
		$logged_in_hook   = static function ( ...$args ) use ( &$events ): void {
			$events['set_logged_in_cookie'][] = $args;
		};

		call_user_func( 'add_action', 'set_auth_cookie', $auth_cookie_hook, 10, 6 );
		call_user_func( 'add_action', 'set_logged_in_cookie', $logged_in_hook, 10, 6 );
		add_filter( 'send_auth_cookies', '__return_false' );

		try {
			$response = $callback();
		} finally {
			call_user_func( 'remove_action', 'set_auth_cookie', $auth_cookie_hook, 10 );
			call_user_func( 'remove_action', 'set_logged_in_cookie', $logged_in_hook, 10 );
			call_user_func( 'remove_filter', 'send_auth_cookies', '__return_false' );
		}

		return [
			'response' => $response,
			'events'   => $events,
		];
	}

	/**
	 * Returns the debug messages from a GraphQL response.
	 */
	private function get_debug_messages( array $response ): array {
		return array_values( call_user_func( 'wp_list_pluck', $response['extensions']['debug'] ?? [], 'message' ) );
	}
}
