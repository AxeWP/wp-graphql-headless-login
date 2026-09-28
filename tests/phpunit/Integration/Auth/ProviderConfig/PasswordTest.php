<?php
/**
 * Tests logging in and out with the Password provider.
 *
 * @package WPGraphQL\Login\Tests\Integration\Auth\ProviderConfig
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Tests\Integration\Auth\ProviderConfig;

use PHPUnit\Framework\Attributes\CoversClass;
use WPGraphQL\Login\Admin\Settings\AccessControlSettings;
use WPGraphQL\Login\Admin\Settings\CookieSettings;
use WPGraphQL\Login\Admin\Settings\ProviderSettings;
use WPGraphQL\Login\Auth\Auth;
use WPGraphQL\Login\Auth\AuthCookie;
use WPGraphQL\Login\Auth\Client;
use WPGraphQL\Login\Auth\ProviderConfig\Password;
use WPGraphQL\Login\Auth\User;
use WPGraphQL\Login\GraphQL\Type\Mutation\LinkUserIdentity;
use WPGraphQL\Login\GraphQL\Type\Mutation\Login;
use WPGraphQL\Login\Tests\Fixtures\CookieJar;
use WPGraphQL\Login\Tests\TestCase;

/**
 * Tests the Password provider mutations, and authenticating with the cookie or tokens they issue.
 */
#[CoversClass( Password::class )]
#[CoversClass( Login::class )]
#[CoversClass( LinkUserIdentity::class )]
#[CoversClass( AuthCookie::class )]
#[CoversClass( Auth::class )]
#[CoversClass( Client::class )]
#[CoversClass( User::class )]
class PasswordTest extends TestCase {
	/**
	 * The administrator user ID.
	 */
	private int $admin;

	/**
	 * The test user ID.
	 */
	private int $test_user;

	/**
	 * {@inheritDoc}
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->admin     = $this->factory()->user->create(
			[
				'role' => 'administrator',
			]
		);
		$this->test_user = $this->factory()->user->create(
			[
				'role'       => 'subscriber',
				'user_login' => 'test_user',
				'user_pass'  => 'test_password',
			]
		);

		$this->set_client_config( 'password', $this->get_provider_config() );
		$this->clearSchema();
	}

	/**
	 * {@inheritDoc}
	 */
	protected function tearDown(): void {
		unset( $_SERVER['HTTP_AUTHORIZATION'], $_SERVER['HTTP_ORIGIN'], $_COOKIE[ LOGGED_IN_COOKIE ] ); // phpcs:ignore WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___COOKIE -- Clears the simulated browser cookie.
		delete_option( ProviderSettings::$settings_prefix . 'password' );
		delete_option( AccessControlSettings::get_slug() );
		delete_option( CookieSettings::get_slug() );
		$this->reset_utils_properties();
		$this->clearSchema();

		parent::tearDown();
	}

	/**
	 * Tests the login mutation's credential and already-logged-in errors, and a successful login.
	 */
	public function test_login_with_no_provisioning(): void {
		$query = $this->login_query();

		// Test bad username.
		$variables = [
			'username' => 'baduser',
			'password' => '12345',
		];

		// Test with no user to match.
		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayHasKey( 'errors', $actual );
		// The error message changes in WP 5.7.
		$this->assertNotEmpty( $actual['errors'][0]['message'] );

		// Test with bad password.
		$variables['username'] = 'test_user';

		$actual = $this->graphql( compact( 'query', 'variables' ) );
		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertEquals( 'The user could not be logged in.', $actual['errors'][0]['message'] );

		// Test with correct credentials.
		$variables['password'] = 'test_password';
		// Test with user already logged in.
		wp_set_current_user( $this->test_user );

		$actual = $this->graphql( compact( 'query', 'variables' ) );
		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertEquals( 'You are already logged in.', $actual['errors'][0]['message'] );

		// Test with user logged in as someone else.
		wp_set_current_user( $this->admin );
		$actual = $this->graphql( compact( 'query', 'variables' ) );
		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertEquals( 'You are already logged in.', $actual['errors'][0]['message'] );

		// Test when logged out.
		wp_set_current_user( 0 );

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertQuerySuccessful(
			$actual,
			[
				$this->expectedObject(
					'login',
					[
						$this->expectedField( 'authToken', self::NOT_FALSY ),
						$this->expectedField( 'authTokenExpiration', self::NOT_FALSY ),
						$this->expectedField( 'refreshToken', self::NOT_FALSY ),
						$this->expectedField( 'refreshTokenExpiration', self::NOT_FALSY ),
						$this->expectedObject(
							'user',
							[
								$this->expectedField( 'databaseId', $this->test_user ),
								$this->expectedObject(
									'auth',
									[

										$this->expectedField( 'isUserSecretRevoked', false ),
										$this->expectedField( 'linkedIdentities', self::IS_NULL ),
										$this->expectedField( 'userSecret', self::NOT_FALSY ),
									]
								),
							]
						),
					]
				),
			]
		);
	}

	/**
	 * Tests that logging in with `useAuthenticationCookie` sets cookies with the configured `SameSite` and `Domain`, which authenticate the viewer until logout.
	 */
	public function test_login_with_authentication_cookie(): void {
		$this->set_up_authenticated_requests( true );
		$this->generate_user_tokens( $this->test_user );
		$_SERVER['HTTP_ORIGIN'] = 'https://example.com';

		$query     = $this->login_query();
		$variables = [
			'username' => 'test_user',
			'password' => 'test_password',
		];

		// Let AuthCookie reach the recording setcookie() fixture.
		add_filter( 'send_auth_cookies', '__return_true', 11 );
		$response = $this->graphql( compact( 'query', 'variables' ) );
		remove_filter( 'send_auth_cookies', '__return_true', 11 );

		$this->assertArrayNotHasKey( 'errors', $response );
		$this->assertSame( [], $this->get_debug_messages( $response ) );
		$this->assertSame( $this->test_user, $response['data']['login']['user']['databaseId'] );
		$this->assertSame( 'test_user', $response['data']['login']['user']['username'] );

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
		$_COOKIE[ LOGGED_IN_COOKIE ] = $logged_in_cookie; // phpcs:ignore WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___COOKIE -- Simulates the browser sending the cookie.
		$GLOBALS['current_user']     = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Forces WordPress to re-determine the current user.

		$query    = $this->viewer_query();
		$response = $this->graphql( compact( 'query' ) );

		$this->assertArrayNotHasKey( 'errors', $response );
		$this->assertSame( [], $this->get_debug_messages( $response ) );
		$this->assertSame( $this->test_user, $response['data']['viewer']['databaseId'] );
		$this->assertSame( 'test_user', $response['data']['viewer']['username'] );
		$this->assertNotEmpty( $response['data']['viewer']['auth']['authToken'] );
		$this->assertNotEmpty( $response['data']['viewer']['auth']['authTokenExpiration'] );

		// Logout before testing the strict sameSite attribute.
		$query    = $this->logout_query();
		$response = $this->graphql( compact( 'query' ) );

		$this->assertArrayNotHasKey( 'errors', $response );
		$this->assertTrue( $response['data']['logout']['success'] );

		// Logging out destroyed the session, so the cookie no longer authenticates.
		$this->reset_utils_properties();
		$GLOBALS['current_user'] = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Forces WordPress to re-determine the current user.

		$query    = $this->viewer_query();
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
		unset( $_COOKIE[ LOGGED_IN_COOKIE ] ); // phpcs:ignore WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___COOKIE -- Clears the simulated browser cookie.
		CookieJar::$cookies = [];

		$query = $this->login_query();

		add_filter( 'send_auth_cookies', '__return_true', 11 );
		$response = $this->graphql( compact( 'query', 'variables' ) );
		remove_filter( 'send_auth_cookies', '__return_true', 11 );

		$this->assertArrayNotHasKey( 'errors', $response );
		$this->assertSame( [], $this->get_debug_messages( $response ) );
		$this->assertSame( $this->test_user, $response['data']['login']['user']['databaseId'] );
		$this->assertSame( 'test_user', $response['data']['login']['user']['username'] );
		$this->assertContains( LOGGED_IN_COOKIE, array_column( CookieJar::$cookies, 'name' ) );

		foreach ( CookieJar::$cookies as $cookie ) {
			$this->assertSame( 'Strict', $cookie['options']['samesite'] );
			$this->assertSame( 'example.com', $cookie['options']['domain'] );
			$this->assertTrue( $cookie['options']['httponly'] );
		}
	}

	/**
	 * Tests that logging in without `useAuthenticationCookie` sets no cookies, and the auth token authenticates the viewer with refreshed tokens in the response headers.
	 */
	public function test_login_without_authentication_cookie(): void {
		$this->set_up_authenticated_requests( false );
		$this->generate_user_tokens( $this->test_user );
		$_SERVER['HTTP_ORIGIN'] = 'https://example.com';

		$query     = $this->login_query();
		$variables = [
			'username' => 'test_user',
			'password' => 'test_password',
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
		$GLOBALS['current_user']       = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Forces WordPress to re-determine the current user.

		$query    = $this->viewer_query();
		$response = $this->graphql( compact( 'query' ) );
		$headers  = apply_filters( 'graphql_response_headers_to_send', [] );

		$this->assertNotEmpty( $headers['X-WPGraphQL-Login-Token'] ?? null );
		$this->assertNotEmpty( $headers['X-WPGraphQL-Login-Refresh-Token'] ?? null );

		$this->assertArrayNotHasKey( 'errors', $response );
		$this->assertSame( [], $this->get_debug_messages( $response ) );
		$this->assertSame( $this->test_user, $response['data']['viewer']['databaseId'] );
		$this->assertSame( 'test_user', $response['data']['viewer']['username'] );
		$this->assertNotEmpty( $response['data']['viewer']['auth']['authToken'] );
		$this->assertNotEmpty( $response['data']['viewer']['auth']['authTokenExpiration'] );
		$this->assertNotEmpty( $response['data']['viewer']['auth']['refreshToken'] );
		$this->assertNotEmpty( $response['data']['viewer']['auth']['refreshTokenExpiration'] );
		$this->assertFalse( $response['data']['viewer']['auth']['isUserSecretRevoked'] );
		$this->assertNotEmpty( $response['data']['viewer']['auth']['userSecret'] );

		$query    = $this->logout_query();
		$response = $this->graphql( compact( 'query' ) );

		$this->assertArrayNotHasKey( 'errors', $response );
		$this->assertTrue( $response['data']['logout']['success'] );
	}

	/**
	 * Tests that the Password provider can't be used to link an identity.
	 */
	public function test_link_user_identity_is_not_supported(): void {
		$query = $this->link_query();

		$variables = [
			'input' => [
				'provider' => 'PASSWORD',
				'userId'   => $this->test_user,
			],
		];

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertEquals( 'You cannot link two identities from the same WordPress site. Please use a different `provider`.', $actual['errors'][0]['message'] );
	}

	/**
	 * Tests that logging in requires a username and password.
	 */
	public function test_login_requires_credentials(): void {
		$query = '
			mutation Login( $input: LoginInput! ) {
				login( input: $input ) {
					authToken
				}
			}
		';

		// Test without credentials.
		$variables = [
			'input' => [
				'provider' => 'PASSWORD',
			],
		];

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertSame( 'The PASSWORD provider requires the use of the `credentials` input arg.', $actual['errors'][0]['message'] );

		// Test with empty credentials.
		$variables['input']['credentials'] = [
			'username' => 'test_user',
			'password' => ' ',
		];

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertSame( 'Missing username or password.', $actual['errors'][0]['message'] );
	}

	/**
	 * Tests that only an authenticated user (or an authentication error) is returned from the provider's user data.
	 */
	public function test_get_user_from_data(): void {
		$config = new Password();
		$user   = get_user_by( 'id', $this->test_user );
		$error  = new \WP_Error( 'incorrect_password', 'Incorrect password.' );

		$this->assertSame( $user, $config->get_user_from_data( $user ) );
		$this->assertSame( $error, $config->get_user_from_data( $error ) );
		$this->assertFalse( $config->get_user_from_data( [ 'user_login' => 'test_user' ] ) );
	}

	/**
	 * Configures the provider, access control, and cookie settings for authenticating follow-up requests.
	 *
	 * @param bool $use_authentication_cookie Whether logging in should set the authentication cookie.
	 */
	private function set_up_authenticated_requests( bool $use_authentication_cookie ): void {
		$this->set_client_config(
			'password',
			$this->get_provider_config(
				[
					'useAuthenticationCookie' => $use_authentication_cookie,
				]
			)
		);

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

		$this->reset_utils_properties();
		$this->clearSchema();

		CookieJar::$cookies = [];
	}

	/**
	 * Returns the Password provider settings.
	 *
	 * @param array<string,mixed> $login_options The provider login options.
	 *
	 * @return array<string,mixed>
	 */
	private function get_provider_config( array $login_options = [] ): array {
		return [
			'name'          => 'Password',
			'slug'          => 'password',
			'order'         => 0,
			'isEnabled'     => true,
			'clientOptions' => [],
			'loginOptions'  => $login_options,
		];
	}

	/**
	 * Returns the login mutation.
	 */
	private function login_query(): string {
		return '
			mutation Login( $username: String!, $password: String! ) {
				login(
					input: {credentials: {username: $username, password: $password }, provider: PASSWORD}
				) {
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
						firstName
						lastName
						email
						username
					}
				}
			}
		';
	}

	/**
	 * Returns the linkUserIdentity mutation.
	 */
	private function link_query(): string {
		return '
			mutation LinkUser( $input: LinkUserIdentityInput! ) {
				linkUserIdentity(
					input: $input
				) {
					success
					user {
						auth {
							linkedIdentities {
								id
								provider
							}
						}
						databaseId
					}
				}
			}
		';
	}

	/**
	 * Returns the logout mutation.
	 */
	private function logout_query(): string {
		return '
			mutation Logout {
				logout( input: {} ) {
					success
				}
			}
		';
	}

	/**
	 * Returns the viewer query, including the viewer's auth data.
	 */
	private function viewer_query(): string {
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
