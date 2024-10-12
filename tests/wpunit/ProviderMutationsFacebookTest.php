<?php
/**
 * Tests Login mutation
 */

use Mockery as m;
use WPGraphQL\Login\Auth\ProviderConfig\OAuth2\Facebook as OAuth2Facebook;
use WPGraphQL\Login\Auth\ProviderConfig\OAuth2\OAuth2Config;
use WPGraphQL\Login\Auth\User;
use WPGraphQL\Login\Vendor\League\OAuth2\Client\Provider\Facebook;

class FooFacebookProvider extends Facebook {
	protected function fetchResourceOwnerDetails( $token ) {
		return json_decode( '{"id": 12345, "name": "mock_name", "username": "mock_username", "first_name": "mock_first_name", "last_name": "mock_last_name", "email": "mock_email@email.com", "Location": "mock_home", "link": "mock_facebook_url"}', true );
	}
}

class FooFacebookProviderConfig extends OAuth2Facebook {
	public function __construct() {
		OAuth2Config::__construct( FooFacebookProvider::class );

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

class ProviderMutationsFacebookTest extends \Tests\WPGraphQL\TestCase\WPGraphQLTestCase {
	/**
	 * @const string The version of the Graph API we want to use for tests.
	 */
	protected const GRAPH_API_VERSION = 'v16.0';

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

		// Set the FB provider config.
		$this->provider_config = [
			'name'          => 'Facebook',
			'slug'          => 'facebook',
			'order'         => 0,
			'isEnabled'     => true,
			'clientOptions' => [
				'clientId'        => 'mock_client_id',
				'clientSecret'    => 'mock_client_secret',
				'redirectUri'     => 'mock_redirect_uri',
				'graphAPIVersion' => self::GRAPH_API_VERSION,
				'enableBetaTier'  => false,
				'scope'           => [
					'email',
					'public_profile',
				],
			],
			'loginOptions'  => [
				'linkExistingUsers'      => false,
				'createUserIfNoneExists' => false,
			],
		];

		$this->tester->set_client_config( 'facebook', $this->provider_config );

		add_filter(
			'graphql_login_provider_config_instances',
			static function ( $providers ) {
				$providers['facebook'] = new FooFacebookProviderConfig();

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
			mutation Login( $code: String!, $state: String ) {
				login(
					input: {oauthResponse: {code: $code, state: $state }, provider: FACEBOOK}
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
			mutation LinkUser( $code: String!, $state: String, $userId: ID! ) {
				linkUserIdentity(
					input: {oauthResponse: {code: $code, state: $state }, provider: FACEBOOK, userId: $userId}
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

		$variables = [
			'code'     => 'mock_authorization_code',
			'provider' => 'FACEBOOK',
		];

		// Test with no user to match.
		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertEquals( 'The user could not be logged in.', $actual['errors'][0]['message'] );

		// Test with user to match.
		User::link_user_identity( $this->test_user, 'facebook', '12345' );

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
												$this->expectedField( 'provider', 'FACEBOOK' ),
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
	}

	public function testLoginWithLinkExistingUsers(): void {
		$config                                      = $this->provider_config;
		$config['loginOptions']['linkExistingUsers'] = true;

		$this->tester->set_client_config( 'facebook', $config );

		$query = $this->login_query();

		$variables = [
			'code'     => 'mock_authorization_code',
			'provider' => 'FACEBOOK',
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
												$this->expectedField( 'provider', 'FACEBOOK' ),
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

		$this->tester->set_client_config( 'facebook', $config );

		$query = $this->login_query();

		$variables = [
			'code'     => 'mock_authorization_code',
			'provider' => 'FACEBOOK',
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
												$this->expectedField( 'provider', 'FACEBOOK' ),
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
			'code'     => 'mock_authorization_code',
			'provider' => 'FACEBOOK',
			'userId'   => $this->test_user,
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
			'code'     => 'mock_authorization_code',
			'provider' => 'FACEBOOK',
			'userId'   => $this->test_user,
		];

		User::link_user_identity( $this->test_user, 'FACEBOOK', '12345' );

		wp_set_current_user( $this->test_user );

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertEquals( 'This identity is already linked to your account.', $actual['errors'][0]['message'] );
	}

	public function testLinkUserIdentityWithConflictingIdentity(): void {
		$query = $this->link_query();

		$variables = [
			'code'     => 'mock_authorization_code',
			'provider' => 'FACEBOOK',
			'userId'   => $this->test_user,
		];

		$new_user = $this->factory()->user->create();

		User::link_user_identity( $new_user, 'facebook', '12345' );

		wp_set_current_user( $this->test_user );

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertEquals( 'This identity is already linked to another account.', $actual['errors'][0]['message'] );
	}

	public function testLinkUserIdentity(): void {
		$query = $this->link_query();

		$variables = [
			'code'     => 'mock_authorization_code',
			'provider' => 'FACEBOOK',
			'userId'   => $this->test_user,
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
												$this->expectedField( 'provider', 'FACEBOOK' ),
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
