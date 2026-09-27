<?php
/**
 * Tests the Password provider login flow.
 *
 * @package Tests\WPGraphQL\Login\Integration\Functional
 */

namespace Tests\WPGraphQL\Login\Integration\Functional;

use Tests\WPGraphQL\Login\Fixtures\CookieJar;
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

		CookieJar::$cookies = [];
	}

	/**
	 * {@inheritDoc}
	 */
	public function tearDown(): void {
		unset( $_SERVER['HTTP_AUTHORIZATION'], $_SERVER['HTTP_ORIGIN'], $_COOKIE[ LOGGED_IN_COOKIE ] );
		delete_option( ProviderSettings::$settings_prefix . 'password' );
		delete_option( AccessControlSettings::get_slug() );
		delete_option( CookieSettings::get_slug() );
		$this->reset_utils_properties();
		$this->clearSchema();

		parent::tearDown();
	}

	/**
	 * Tests cookie authentication, including the `SameSite` and `Domain` cookie settings.
	 */
	public function test_cookie_auth(): void {
		$user_id = $this->factory()->user->create(
			[
				'role'       => 'administrator',
				'user_login' => 'testuser',
				'user_pass'  => 'testpass',
			]
		);

		$this->generate_user_tokens( $user_id );
		$_SERVER['HTTP_ORIGIN'] = 'https://example.com';

		$query     = $this->get_login_mutation();
		$variables = [
			'username' => 'testuser',
			'password' => 'testpass',
		];

		// Let AuthCookie reach the recording setcookie() fixture.
		add_filter( 'send_auth_cookies', '__return_true', 11 );
		$response = $this->graphql( compact( 'query', 'variables' ) );
		remove_filter( 'send_auth_cookies', '__return_true', 11 );

		$this->assertArrayNotHasKey( 'errors', $response );
		$this->assertSame( [], $this->get_debug_messages( $response ) );
		$this->assertSame( $user_id, $response['data']['login']['user']['databaseId'] );
		$this->assertSame( 'testuser', $response['data']['login']['user']['username'] );

		$logged_in_cookie = array_column( CookieJar::$cookies, 'value', 'name' )[ LOGGED_IN_COOKIE ] ?? '';
		$this->assertNotEmpty( wp_parse_auth_cookie( $logged_in_cookie, 'logged_in' ) );

		foreach ( CookieJar::$cookies as $cookie ) {
			$this->assertSame( 'Lax', $cookie['options']['samesite'] );
			$this->assertSame( '', $cookie['options']['domain'] );
			$this->assertTrue( $cookie['options']['httponly'] );
		}

		// Authenticate the viewer from the logged-in cookie alone.
		$this->reset_utils_properties();
		$_SERVER['HTTP_ORIGIN']      = site_url();
		$_COOKIE[ LOGGED_IN_COOKIE ] = $logged_in_cookie;
		$GLOBALS['current_user']     = null;

		$query    = $this->get_viewer_query();
		$response = $this->graphql( compact( 'query' ) );

		$this->assertArrayNotHasKey( 'errors', $response );
		$this->assertSame( [], $this->get_debug_messages( $response ) );
		$this->assertSame( $user_id, $response['data']['viewer']['databaseId'] );
		$this->assertSame( 'testuser', $response['data']['viewer']['username'] );
		$this->assertNotEmpty( $response['data']['viewer']['auth']['authToken'] );
		$this->assertNotEmpty( $response['data']['viewer']['auth']['authTokenExpiration'] );

		// Logout before testing the strict sameSite attribute.
		$query    = $this->get_logout_mutation();
		$response = $this->graphql( compact( 'query' ) );

		$this->assertArrayNotHasKey( 'errors', $response );
		$this->assertTrue( $response['data']['logout']['success'] );

		// Logging out destroyed the session, so the cookie no longer authenticates.
		$this->reset_utils_properties();
		$GLOBALS['current_user'] = null;

		$query    = $this->get_viewer_query();
		$response = $this->graphql( compact( 'query' ) );

		$this->assertArrayNotHasKey( 'errors', $response );
		$this->assertNull( $response['data']['viewer'] );

		// Test with strict sameSite attribute.
		update_option(
			CookieSettings::get_slug(),
			[
				'hasLogoutMutation'                => true,
				'hasAccessControlAllowCredentials' => true,
				'sameSiteOption'                   => 'Strict',
				'cookieDomain'                     => 'example.com',
			]
		);
		$this->reset_utils_properties();
		unset( $_COOKIE[ LOGGED_IN_COOKIE ] );
		CookieJar::$cookies = [];

		$query = $this->get_login_mutation();

		add_filter( 'send_auth_cookies', '__return_true', 11 );
		$response = $this->graphql( compact( 'query', 'variables' ) );
		remove_filter( 'send_auth_cookies', '__return_true', 11 );

		$this->assertArrayNotHasKey( 'errors', $response );
		$this->assertSame( [], $this->get_debug_messages( $response ) );
		$this->assertSame( $user_id, $response['data']['login']['user']['databaseId'] );
		$this->assertSame( 'testuser', $response['data']['login']['user']['username'] );
		$this->assertContains( LOGGED_IN_COOKIE, array_column( CookieJar::$cookies, 'name' ) );

		foreach ( CookieJar::$cookies as $cookie ) {
			$this->assertSame( 'Strict', $cookie['options']['samesite'] );
			$this->assertSame( 'example.com', $cookie['options']['domain'] );
			$this->assertTrue( $cookie['options']['httponly'] );
		}
	}

	/**
	 * Tests token authentication, including the refreshed tokens in the response headers.
	 */
	public function test_token_auth(): void {
		$this->set_client_config( 'password', $this->get_password_provider_config( false ) );

		$user_id = $this->factory()->user->create(
			[
				'role'       => 'administrator',
				'user_login' => 'testuser',
				'user_pass'  => 'testpass',
			]
		);

		$this->generate_user_tokens( $user_id );
		$_SERVER['HTTP_ORIGIN'] = 'https://example.com';

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
		$this->assertSame( [], $this->get_debug_messages( $login['response'] ) );
		$this->assertEmpty( $login['events']['set_auth_cookie'] );
		$this->assertEmpty( $login['events']['set_logged_in_cookie'] );

		$auth_token = $login['response']['data']['login']['authToken'];
		$this->assertNotEmpty( $auth_token );

		// Authenticate the viewer from the auth token alone.
		$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $auth_token;
		$GLOBALS['current_user']       = null;

		$query    = $this->get_viewer_query();
		$response = $this->graphql( compact( 'query' ) );
		$headers  = apply_filters( 'graphql_response_headers_to_send', [] );

		$this->assertNotEmpty( $headers['X-WPGraphQL-Login-Token'] ?? null );
		$this->assertNotEmpty( $headers['X-WPGraphQL-Login-Refresh-Token'] ?? null );

		$this->assertArrayNotHasKey( 'errors', $response );
		$this->assertSame( [], $this->get_debug_messages( $response ) );
		$this->assertSame( $user_id, $response['data']['viewer']['databaseId'] );
		$this->assertSame( 'testuser', $response['data']['viewer']['username'] );
		$this->assertNotEmpty( $response['data']['viewer']['auth']['authToken'] );
		$this->assertNotEmpty( $response['data']['viewer']['auth']['authTokenExpiration'] );
		$this->assertNotEmpty( $response['data']['viewer']['auth']['refreshToken'] );
		$this->assertNotEmpty( $response['data']['viewer']['auth']['refreshTokenExpiration'] );
		$this->assertFalse( $response['data']['viewer']['auth']['isUserSecretRevoked'] );
		$this->assertNotEmpty( $response['data']['viewer']['auth']['userSecret'] );

		$query    = $this->get_logout_mutation();
		$response = $this->graphql( compact( 'query' ) );

		$this->assertArrayNotHasKey( 'errors', $response );
		$this->assertTrue( $response['data']['logout']['success'] );
	}

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

	private function get_logout_mutation(): string {
		return '
			mutation Logout {
				logout( input: {} ) {
					success
				}
			}
		';
	}

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
}
