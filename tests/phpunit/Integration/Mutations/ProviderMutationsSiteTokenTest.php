<?php
/**
 * Tests the login and linkUserIdentity mutations for the Site Token provider.
 *
 * @package Tests\WPGraphQL\Login\Integration\Mutations
 */

declare( strict_types = 1 );

namespace Tests\WPGraphQL\Login\Integration\Mutations;

use WPGraphQL\Login\Admin\Settings\AccessControlSettings;
use WPGraphQL\Login\Auth\User;

/**
 * Tests the Site Token provider mutations.
 */
class ProviderMutationsSiteTokenTest extends \Tests\WPGraphQL\Login\TestCase {
	/**
	 * The administrator user ID.
	 *
	 * @var int
	 */
	public $admin;

	/**
	 * The test user ID.
	 *
	 * @var int
	 */
	public $test_user;

	/**
	 * The provider config settings.
	 *
	 * @var array<string,mixed>
	 */
	public $provider_config;

	/**
	 * {@inheritDoc}
	 */
	public function setUp(): void {
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
	public function tearDown(): void {
		delete_option( AccessControlSettings::get_slug() );
		$this->reset_utils_properties();
		wp_delete_user( $this->test_user );
		$this->clearSchema();

		parent::tearDown();
	}

	/**
	 * The `login` mutation.
	 */
	public function login_query(): string {
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
	 * The `linkUserIdentity` mutation.
	 */
	public function link_query(): string {
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
	 * Tests logging in with `shouldBlockUnauthorizedDomains` disabled.
	 */
	public function testLoginWithoutBlockedAuthorizedDomains(): void {
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

		// Compat for WPGraphQL v1.x.
		$debug_message = $actual['errors'][0]['extensions']['debugMessage'] ?? $actual['errors'][0]['debugMessage'];
		$this->assertEquals( 'Provider siteToken is not enabled.', $debug_message );
	}

	/**
	 * Tests logging in with user provisioning disabled.
	 */
	public function testLoginWithNoProvisioning(): void {
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

		// Test user already logged in.
		wp_set_current_user( $this->test_user );
		$variables['input']['identity'] = 'test_user';

		$this->assertEquals( 'The user could not be logged in.', $actual['errors'][0]['message'] );

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

		// Cleanup.
		unset( $_SERVER['HTTP_X_MY_SECRET_AUTH_TOKEN'] );
	}

	/**
	 * Tests linking an identity that is already linked to another user.
	 */
	public function testLinkUserIdentityWithConflictingIdentity(): void {
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
					'metaKey'                 => 'my_meta_key',
				],
			]
		);
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

		// Cleanup.
		unset( $_SERVER['HTTP_X_MY_SECRET_AUTH_TOKEN'] );
	}

	/**
	 * Tests linking an identity to the user.
	 */
	public function testLinkUserIdentity(): void {
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
		$admin_user = $this->factory()->user->create(
			[
				'role' => 'administrator',
			]
		);
		wp_set_current_user( $admin_user );

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertEquals( 'You must be logged in as the user to link your identity.', $actual['errors'][0]['message'] );

		wp_set_current_user( $this->test_user );

		// Test with no identity
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

		// Cleanup.
		unset( $_SERVER['HTTP_X_MY_SECRET_AUTH_TOKEN'] );
	}
}
