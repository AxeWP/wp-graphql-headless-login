<?php
/**
 * Tests Login mutation
 */

use Mockery as m;
use WPGraphQL\Login\Auth\ProviderConfig\OAuth2\Generic;
use WPGraphQL\Login\Auth\ProviderConfig\OAuth2\OAuth2Config;
use WPGraphQL\Login\Auth\User;
use WPGraphQL\Login\Vendor\League\OAuth2\Client\Provider\GenericProvider;

class FooGenericProvider extends GenericProvider {
	protected function fetchResourceOwnerDetails( $token ) {
		return json_decode( '{"id": 12345, "name": "mock_name", "username": "mock_username", "first_name": "mock_first_name", "last_name": "mock_last_name", "email": "mock_email@email.com"}', true );
	}

	public function getAuthorizationUrl( array $options = [] ) {
		return parent::getAuthorizationUrl(
			[
				'state' => 'someState',
			]
		);
	}
}

class FooGenericProviderConfig extends Generic {
	public function __construct() {
		OAuth2Config::__construct( FooGenericProvider::class );

		// Mock and set the http client on the provider.
		$response = m::mock( 'WPGraphQL\Login\Vendor\Psr\Http\Message\ResponseInterface' );
		$response->shouldReceive( 'getHeader' )
			->times( 1 )
			->andReturn( [ 'Content-Type' => 'application/json' ] );
		$response->shouldReceive( 'getBody' )
			->times( 1 )
			->andReturn(
				\WPGraphQL\Login\Vendor\GuzzleHttp\Psr7\Utils::streamFor( '{"access_token":"mock_access_token","token_type":"bearer","expires_in":3600}' )
			);

		$http_client = m::mock( 'WPGraphQL\Login\Vendor\GuzzleHttp\ClientInterface' );
		$http_client->shouldReceive( 'send' )->times( 1 )->andReturn( $response );
		$this->provider->setHttpClient( $http_client );
	}
}

class ProviderMutationsGenericTest extends \Tests\WPGraphQL\TestCase\WPGraphQLTestCase {
	/**
	 * @var \WpunitTester
	 */
	public $tester;
	public $test_user;
	public $provider_config;
	public $provider;

	/**
	 * {@inheritDoc}
	 */
	public function setUp(): void {
		parent::setUp();

		$this->test_user = $this->factory()->user->create(
			[
				'role' => 'subscriber',
			]
		);

		// Set the provider config.
		$this->provider_config = [
			'name'          => 'Generic - OAuth2',
			'slug'          => 'oauth2-generic',
			'order'         => 0,
			'isEnabled'     => true,
			'clientOptions' => [
				'clientId'                => 'mock_client_id',
				'clientSecret'            => 'mock_client_secret',
				'redirectUri'             => 'mock_redirect_uri',
				'urlAuthorize'            => 'http://example.com/authorize',
				'urlAccessToken'          => 'http://example.com/token',
				'urlResourceOwnerDetails' => 'http://example.com/user',
				'scope'                   => [
					'email',
					'public_profile',
				],
			],
			'loginOptions'  => [
				'linkExistingUsers'      => false,
				'createUserIfNoneExists' => false,
			],
		];

		$this->tester->set_client_config( 'oauth2-generic', $this->provider_config );

		add_filter(
			'graphql_login_provider_config_instances',
			static function ( $providers ) {
				$providers['oauth2-generic'] = new FooGenericProviderConfig();

				return $providers;
			}
		);
		$this->clearSchema();
	}

	/**
	 * {@inheritDoc}
	 */
	public function tearDown(): void {
		$this->tester->reset_utils_properties();
		$this->clearSchema();

		parent::tearDown();
	}

	public function login_query(): string {
		return '
			mutation Login( $input: LoginInput! ) {
				login(
					input: $input
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

	public function link_query(): string {
		return '
			mutation LinkUser( $input : LinkUserIdentityInput! ) {
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

	public function testLoginWithNoProvisioning(): void {
		$query = $this->login_query();

		// Test with no oauthResponse.
		$variables = [
			'input' => [
				'provider' => 'OAUTH2_GENERIC',
			],
		];

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertEquals( 'The oauth2-generic provider requires the use of the `oauthResponse` input arg.', $actual['errors'][0]['message'] );

		// Test with no user to match.
		$variables['input']['oauthResponse'] = [
			'code' => 'mock_authorization_code',
		];
		$actual                              = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertEquals( 'The user could not be logged in.', $actual['errors'][0]['message'] );

		// Test with user to match.
		User::link_user_identity( $this->test_user, 'oauth2-generic', '12345' );

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
												$this->expectedField( 'id', '12345' ),
												$this->expectedField( 'provider', 'OAUTH2_GENERIC' ),
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

		// Currently we dont overwrite existing properties.
		$this->assertNotEquals( 'mock_email@email.com', $actual['data']['login']['user']['email'] );
		$this->assertNotEquals( 'mock_first_name', $actual['data']['login']['user']['firstName'] );
		$this->assertNotEquals( 'mock_last_name', $actual['data']['login']['user']['lastName'] );
		$this->assertNotEquals( 'mock_username', $actual['data']['login']['user']['username'] );

		// Test with bad state
		wp_set_current_user( 0 );
		$_SESSION['oauth2state'] = 'mock_state';

		$variables['input']['oauthResponse']['state'] = 'bad_state';

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertEquals( 'The state returned from the oauth2-generic response does not match.', $actual['errors'][0]['message'] );

		// test with good state.
		$variables['input']['oauthResponse']['state'] = 'mock_state';

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
												$this->expectedField( 'id', '12345' ),
												$this->expectedField( 'provider', 'OAUTH2_GENERIC' ),
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

	public function testLoginWithLinkExistingUsers(): void {
		$config                                      = $this->provider_config;
		$config['loginOptions']['linkExistingUsers'] = true;

		$this->tester->set_client_config( 'oauth2-generic', $config );

		$query = $this->login_query();

		$variables = [
			'input' => [
				'oauthResponse' => [
					'code' => 'mock_authorization_code',
				],
				'provider'      => 'OAUTH2_GENERIC',
			],
		];

		// Test with no user to match.
		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertEquals( 'The user could not be logged in.', $actual['errors'][0]['message'] );

		// Test with user to match.
		wp_update_user(
			[
				'ID'         => $this->test_user,
				'user_email' => 'mock_email@email.com',
			]
		);

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
												$this->expectedField( 'id', '12345' ),
												$this->expectedField( 'provider', 'OAUTH2_GENERIC' ),
											],
											0
										),
										$this->expectedField( 'userSecret', self::NOT_FALSY ),
									]
								),
								$this->expectedField( 'email', 'mock_email@email.com' ),
							]
						),
					]
				),
			]
		);

		// Currently we dont overwrite existing properties.
		$this->assertNotEquals( 'mock_first_name', $actual['data']['login']['user']['firstName'] );
		$this->assertNotEquals( 'mock_last_name', $actual['data']['login']['user']['lastName'] );
		$this->assertNotEquals( 'mock_username', $actual['data']['login']['user']['username'] );
	}

	public function testLoginWithCreateUser(): void {
		$config = $this->provider_config;
		$config['loginOptions']['createUserIfNoneExists'] = true;

		// Test with user to match.
		wp_update_user(
			[
				'ID'         => $this->test_user,
				'user_email' => 'mock_email@email.com',
			]
		);

		$this->tester->set_client_config( 'oauth2-generic', $config );

		$query = $this->login_query();

		$variables = [
			'input' => [
				'oauthResponse' => [
					'code' => 'mock_authorization_code',
				],
				'provider'      => 'OAUTH2_GENERIC',
			],
		];

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertEquals( 'Sorry, that email address is already used!', $actual['errors'][0]['message'] );

		// Test with no user to match.
		wp_update_user(
			[
				'ID'         => $this->test_user,
				'user_email' => 'some_other_email@email.com',
			]
		);

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
								$this->expectedObject(
									'auth',
									[

										$this->expectedField( 'isUserSecretRevoked', false ),
										$this->expectedNode(
											'linkedIdentities',
											[
												$this->expectedField( 'id', '12345' ),
												$this->expectedField( 'provider', 'OAUTH2_GENERIC' ),
											],
											0
										),
										$this->expectedField( 'userSecret', self::NOT_FALSY ),
									]
								),
								$this->expectedField( 'email', 'mock_email@email.com' ),
								$this->expectedField( 'firstName', 'mock_first_name' ),
								$this->expectedField( 'lastName', 'mock_last_name' ),
								$this->expectedField( 'username', 'mock_username' ),
							]
						),
					]
				),
			]
		);

		// A new user will have a new database ID.
		$this->assertNotEquals( $this->test_user, $actual['data']['login']['user']['databaseId'] );
	}

	public function testLinkUserIdentityWithNoPermissions(): void {
		$query = $this->link_query();

		$variables = [
			'input' => [
				'oauthResponse' => [
					'code' => 'mock_authorization_code',
				],
				'provider'      => 'OAUTH2_GENERIC',
				'userId'        => $this->test_user,
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
	}

	public function testLinkUserIdentityWithExistingIdentity(): void {
		$query = $this->link_query();

		$variables = [
			'input' => [
				'oauthResponse' => [
					'code' => 'mock_authorization_code',
				],
				'provider'      => 'OAUTH2_GENERIC',
				'userId'        => $this->test_user,
			],
		];

		User::link_user_identity( $this->test_user, 'oauth2-generic', '12345' );

		wp_set_current_user( $this->test_user );

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertEquals( 'This identity is already linked to your account.', $actual['errors'][0]['message'] );
	}

	public function testLinkUserIdentityWithConflictingIdentity(): void {
		$query = $this->link_query();

		$variables = [
			'input' => [
				'oauthResponse' => [
					'code' => 'mock_authorization_code',
				],
				'provider'      => 'OAUTH2_GENERIC',
				'userId'        => $this->test_user,
			],
		];

		$new_user = $this->factory()->user->create();

		User::link_user_identity( $new_user, 'oauth2-generic', '12345' );

		wp_set_current_user( $this->test_user );

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertEquals( 'This identity is already linked to another account.', $actual['errors'][0]['message'] );
	}

	public function testLinkUserIdentity(): void {
		$query = $this->link_query();

		$variables = [
			'input' => [
				'oauthResponse' => [
					'code' => 'mock_authorization_code',
				],
				'provider'      => 'OAUTH2_GENERIC',
				'userId'        => $this->test_user,
			],
		];

		wp_set_current_user( $this->test_user );

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
												$this->expectedField( 'provider', 'OAUTH2_GENERIC' ),
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
}
