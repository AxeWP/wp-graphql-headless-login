<?php
/**
 * Tests the login and linkUserIdentity mutations for the Site Token provider.
 *
 * @package WPGraphQL\Login\Tests\Integration\Auth\ProviderConfig
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Tests\Integration\Auth\ProviderConfig;

use PHPUnit\Framework\Attributes\CoversClass;
use WPGraphQL\Login\Admin\Settings\AccessControlSettings;
use WPGraphQL\Login\Admin\Settings\ProviderSettings;
use WPGraphQL\Login\Auth\Auth;
use WPGraphQL\Login\Auth\Client;
use WPGraphQL\Login\Auth\ProviderConfig\SiteToken;
use WPGraphQL\Login\Auth\User;
use WPGraphQL\Login\GraphQL\Type\Mutation\LinkUserIdentity;
use WPGraphQL\Login\GraphQL\Type\Mutation\Login;
use WPGraphQL\Login\Tests\TestCase;

/**
 * Tests the Site Token provider mutations.
 */
#[CoversClass( SiteToken::class )]
#[CoversClass( Login::class )]
#[CoversClass( LinkUserIdentity::class )]
#[CoversClass( Auth::class )]
#[CoversClass( Client::class )]
#[CoversClass( User::class )]
class SiteTokenTest extends TestCase {
	/**
	 * The administrator user ID.
	 */
	private int $admin;

	/**
	 * The test user ID.
	 */
	private int $test_user;

	/**
	 * The provider config settings.
	 *
	 * @var array<string,mixed>
	 */
	private array $provider_config;

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

		// Set the provider config.
		$this->provider_config = [
			'name'          => 'Site Token',
			'slug'          => 'siteToken',
			'order'         => 0,
			'isEnabled'     => true,
			'clientOptions' => [
				'headerKey' => '',
				'secretKey' => 'some_secret',
			],
			'loginOptions'  => [
				'useAuthenticationCookie' => true,
				'metaKey'                 => 'login',
			],
		];
		$this->set_client_config( 'siteToken', $this->provider_config );

		update_option(
			AccessControlSettings::get_slug(),
			[
				'shouldBlockUnauthorizedDomains' => true,
			]
		);
		$_SERVER['HTTP_ORIGIN'] = site_url();

		$this->reset_utils_properties();

		$this->clearSchema();
	}

	/**
	 * {@inheritDoc}
	 */
	protected function tearDown(): void {
		unset( $_SERVER['HTTP_ORIGIN'], $_SERVER['HTTP_X_MY_SECRET_AUTH_TOKEN'] );
		delete_option( ProviderSettings::$settings_prefix . 'siteToken' );
		delete_option( AccessControlSettings::get_slug() );
		$this->reset_utils_properties();
		wp_delete_user( $this->test_user );
		$this->clearSchema();

		parent::tearDown();
	}

	/**
	 * Tests logging in with `shouldBlockUnauthorizedDomains` disabled.
	 */
	public function test_login_without_blocked_authorized_domains(): void {
		delete_option( AccessControlSettings::get_slug() );
		$this->reset_utils_properties();

		$query = $this->login_query();

		$variables = [
			'input' => [
				'identity' => 'test_user',
				'provider' => 'SITETOKEN',
			],
		];

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayHasKey( 'errors', $actual );

		$this->assertSame( 'Provider siteToken is not enabled.', $actual['errors'][0]['message'] );
	}

	/**
	 * Tests the login mutation's header, identity, and already-logged-in errors, and a successful login.
	 */
	public function test_login_with_no_provisioning(): void {
		$query = $this->login_query();

		$variables = [
			'input' => [
				'identity' => 'test_user',
				'provider' => 'SITETOKEN',
			],
		];

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertEquals( 'Header key for site token authentication is not defined.', $actual['errors'][0]['message'] );

		// Test with header.
		$this->provider_config['clientOptions']['headerKey'] = 'X-My-Secret-Auth-Token';

		$this->set_client_config( 'siteToken', $this->provider_config );

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertEquals( 'Missing site token in custom header.', $actual['errors'][0]['message'] );

		// Test with bad header.
		$_SERVER['HTTP_X_MY_SECRET_AUTH_TOKEN'] = 'bad_secret';

		$actual = $this->graphql( compact( 'query', 'variables' ) );
		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertEquals( 'Invalid site token.', $actual['errors'][0]['message'] );

		// Test with no identity.
		$_SERVER['HTTP_X_MY_SECRET_AUTH_TOKEN'] = 'some_secret';
		unset( $variables['input']['identity'] );

		$actual = $this->graphql( compact( 'query', 'variables' ) );
		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertEquals( 'The SITE_TOKEN provider requires the use of the `identity` input arg.', $actual['errors'][0]['message'] );

		// Test with bad identity.
		$variables['input']['identity'] = 'bad_user';

		$actual = $this->graphql( compact( 'query', 'variables' ) );
		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertEquals( 'The user could not be logged in.', $actual['errors'][0]['message'] );

		// Test with an empty identity, which shouldn't match users without one.
		$variables['input']['identity'] = '';

		$actual = $this->graphql( compact( 'query', 'variables' ) );
		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertEquals( 'The user could not be logged in.', $actual['errors'][0]['message'] );

		// Test user already logged in.
		wp_set_current_user( $this->test_user );
		$variables['input']['identity'] = 'test_user';

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
										$this->expectedNode(
											'linkedIdentities',
											[
												$this->expectedField( 'id', 'test_user' ),
												$this->expectedField( 'provider', 'SITETOKEN' ),
											],
											0
										),
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
	 * Tests that logging in by email is blocked until the request origin is authorized, and then sets the logged-in cookie.
	 */
	public function test_login_by_email_from_authorized_origin_sets_auth_cookie(): void {
		$this->provider_config['clientOptions']['headerKey'] = 'X-My-Secret-Auth-Token';
		$this->provider_config['loginOptions']['metaKey']    = 'email';
		$this->set_client_config( 'siteToken', $this->provider_config );

		delete_option( AccessControlSettings::get_slug() );
		$this->reset_utils_properties();
		unset( $_SERVER['HTTP_ORIGIN'] );

		$this->factory()->user->create(
			[
				'role'       => 'administrator',
				'user_login' => 'testuser',
				'user_pass'  => 'testpass',
				'user_email' => 'some_email@test.com',
			]
		);

		$query = $this->login_query();

		$variables = [
			'input' => [
				'identity' => 'some_email@test.com',
				'provider' => 'SITETOKEN',
			],
		];

		$_SERVER['HTTP_X_MY_SECRET_AUTH_TOKEN'] = 'some_secret';

		$blocked = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayHasKey( 'errors', $blocked );
		$this->assertSame( 'Provider siteToken is not enabled.', $blocked['errors'][0]['message'] );

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
	 * Tests linking an identity that is already linked to another user.
	 */
	public function test_link_user_identity_with_conflicting_identity(): void {
		$this->provider_config['clientOptions']['headerKey'] = 'X-My-Secret-Auth-Token';
		$this->provider_config['loginOptions']['metaKey']    = 'my_meta_key';
		$this->set_client_config( 'siteToken', $this->provider_config );

		$query = $this->link_query();

		$variables                              = [
			'input' => [
				'provider' => 'SITETOKEN',
				'userId'   => $this->test_user,
				'identity' => '12345',
			],
		];
		$_SERVER['HTTP_X_MY_SECRET_AUTH_TOKEN'] = 'some_secret';

		$new_user = $this->factory()->user->create();

		User::link_user_identity( $new_user, 'siteToken', '12345' );

		wp_set_current_user( $this->test_user );

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertEquals( 'This identity is already linked to another account.', $actual['errors'][0]['message'] );
	}

	/**
	 * Tests the linkUserIdentity mutation's permission, identity, and header errors, and a successful link.
	 */
	public function test_link_user_identity(): void {
		$query = $this->link_query();

		$variables = [
			'input' => [
				'provider' => 'SITETOKEN',
				'userId'   => $this->test_user,
			],
		];

		// Test logged out.
		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertEquals( 'You must be logged in to link your identity.', $actual['errors'][0]['message'] );

		// Test with different user.
		wp_set_current_user( $this->admin );

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertEquals( 'You must be logged in as the user to link your identity.', $actual['errors'][0]['message'] );

		wp_set_current_user( $this->test_user );

		// Test with no identity.
		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertEquals( 'The SITE_TOKEN provider requires the use of the `identity` input arg.', $actual['errors'][0]['message'] );

		// Test with no header key.
		$variables['input']['identity'] = '12345';

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertEquals( 'Header key for site token authentication is not defined.', $actual['errors'][0]['message'] );

		// Test with header key.
		$this->provider_config['clientOptions']['headerKey'] = 'X-My-Secret-Auth-Token';

		$this->set_client_config( 'siteToken', $this->provider_config );

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertEquals( 'Missing site token in custom header.', $actual['errors'][0]['message'] );

		// Test with bad header.
		$_SERVER['HTTP_X_MY_SECRET_AUTH_TOKEN'] = 'bad_secret';

		$actual = $this->graphql( compact( 'query', 'variables' ) );
		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertEquals( 'Invalid site token.', $actual['errors'][0]['message'] );

		// Test with header.
		$_SERVER['HTTP_X_MY_SECRET_AUTH_TOKEN'] = 'some_secret';

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertQuerySuccessful(
			$actual,
			[
				$this->expectedObject(
					'linkUserIdentity',
					[
						$this->expectedField( 'success', true ),
						$this->expectedObject(
							'user',
							[
								$this->expectedField( 'databaseId', $this->test_user ),
								$this->expectedObject(
									'auth',
									[
										$this->expectedNode(
											'linkedIdentities',
											[
												$this->expectedField( 'id', '12345' ),
												$this->expectedField( 'provider', 'SITETOKEN' ),
											],
											0
										),
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
	 * Returns the login mutation.
	 */
	private function login_query(): string {
		return '
			mutation LoginWithSiteToken( $input: LoginInput! ) {
				login( input: $input ) {
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
}
