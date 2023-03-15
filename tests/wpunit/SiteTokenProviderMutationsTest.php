<?php
/**
 * Tests Login mutation
 */

class SiteTokenProviderMutationsTest extends \Tests\WPGraphQL\TestCase\WPGraphQLTestCase {

	public $tester;
	public $admin;
	public $test_user;
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

		// Set the FB provider config.
		$this->provider_config = [
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
				'metaKey'                 => 'login',
			],
		];

		$this->tester->set_client_config( 'siteToken', $this->provider_config );
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

	public function login_query() : string {
		return '
			mutation LoginWithSiteToken( $identity: String!) {
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
						firstName
						lastName
						email
						username
					}
				}
			}
		';
	}

	public function testLoginWithNoProvisioning() : void {
		$query = $this->login_query();

		$variables = [
			'identity' => 'test_user',
		];

		// Test with no header.
		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertEquals( 'Missing site token in custom header.', $actual['errors'][0]['message'] );

		// Test with bad header.
		$_SERVER['HTTP_X_MY_SECRET_AUTH_TOKEN'] = 'bad_secret';

		$actual = $this->graphql( compact( 'query', 'variables' ) );
		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertEquals( 'Invalid site token.', $actual['errors'][0]['message'] );

		// Test with bad identity.
		$_SERVER['HTTP_X_MY_SECRET_AUTH_TOKEN'] = 'some_secret';
		$variables['identity']                  = 'bad_user';

		$actual = $this->graphql( compact( 'query', 'variables' ) );
		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertEquals( 'The user could not be logged in.', $actual['errors'][0]['message'] );

		// Test user already logged in.
		wp_set_current_user( $this->test_user );
		$variables['identity'] = 'test_user';

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
}
