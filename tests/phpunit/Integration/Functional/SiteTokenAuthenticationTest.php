<?php
/**
 * Tests the Site Token provider login flow end-to-end.
 *
 * @package Tests\WPGraphQL\Login\Integration\Functional
 */

namespace Tests\WPGraphQL\Login\Integration\Functional;

use Tests\WPGraphQL\Login\TestCase;
use WPGraphQL\Login\Admin\Settings\AccessControlSettings;
use WPGraphQL\Login\Admin\Settings\ProviderSettings;

/**
 * Tests logging in with the Site Token provider.
 */
class SiteTokenAuthenticationTest extends TestCase {
	/**
	 * {@inheritDoc}
	 */
	public function setUp(): void {
		parent::setUp();

		$this->set_client_config(
			'siteToken',
			[
				'name'          => 'Site Token',
				'slug'          => 'siteToken',
				'order'         => 0,
				'isEnabled'     => true,
				'clientOptions' => [
					'headerKey' => 'X-My-Secret-Auth-Token',
					'secretKey' => 'some_secret',
				],
				'loginOptions'  => [
					'useAuthenticationCookie' => true,
					'metaKey'                 => 'email',
				],
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
		unset( $_SERVER['HTTP_ORIGIN'], $_SERVER['HTTP_X_MY_SECRET_AUTH_TOKEN'] );
		delete_option( ProviderSettings::$settings_prefix . 'siteToken' );
		delete_option( AccessControlSettings::get_slug() );
		$this->reset_utils_properties();
		$this->clearSchema();

		parent::tearDown();
	}

	/**
	 * Tests that a site token login honors access control and sets the auth cookie.
	 */
	public function test_login_with_site_token_respects_access_control_and_sets_auth_cookie(): void {
		$this->factory()->user->create(
			[
				'role'       => 'administrator',
				'user_login' => 'testuser',
				'user_pass'  => 'testpass',
				'user_email' => 'some_email@test.com',
			]
		);

		$query = '
			mutation LoginWithSiteToken( $identity: String! ) {
				login( input: { identity: $identity, provider: SITETOKEN } ) {
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
						email
					}
				}
			}
		';

		$variables = [
			'identity' => 'some_email@test.com',
		];

		$_SERVER['HTTP_X_MY_SECRET_AUTH_TOKEN'] = 'some_secret';

		$blocked = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayHasKey( 'errors', $blocked );
		$debug_message = $blocked['errors'][0]['extensions']['debugMessage'] ?? $blocked['errors'][0]['debugMessage'] ?? '';
		$this->assertSame( 'Provider siteToken is not enabled.', $debug_message );

		update_option(
			AccessControlSettings::get_slug(),
			[
				'shouldBlockUnauthorizedDomains' => true,
				'hasSiteAddressInOrigin'         => true,
			]
		);
		$this->reset_utils_properties();
		$this->reset_provider_registry();

		$_SERVER['HTTP_ORIGIN'] = site_url();

		$allowed = $this->capture_auth_cookie_events(
			function () use ( $query, $variables ) {
				return $this->graphql( compact( 'query', 'variables' ) );
			}
		);

		$response = $allowed['response'];
		$events   = $allowed['events'];

		$this->assertArrayNotHasKey( 'errors', $response );
		$this->assertSame( [], $this->get_debug_messages( $response ) );
		$this->assertNotEmpty( $response['data']['login']['authToken'] );
		$this->assertNotEmpty( $response['data']['login']['authTokenExpiration'] );
		$this->assertNotEmpty( $response['data']['login']['refreshToken'] );
		$this->assertNotEmpty( $response['data']['login']['refreshTokenExpiration'] );
		$this->assertSame( 'testuser', $response['data']['login']['user']['username'] );
		$this->assertSame( 'some_email@test.com', $response['data']['login']['user']['email'] );
		$this->assertNotEmpty( $events['set_logged_in_cookie'] );
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
