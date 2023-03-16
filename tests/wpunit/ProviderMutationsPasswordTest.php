<?php
/**
 * Tests Login mutation
 */

class ProviderMutationsPasswordTest extends \Tests\WPGraphQL\TestCase\WPGraphQLTestCase {
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
			'name'          => 'Password',
			'slug'          => 'password',
			'order'         => 0,
			'isEnabled'     => true,
			'clientOptions' => [],
			'loginOptions'  => [],
		];

		$this->tester->set_client_config( 'password', $this->provider_config );
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

	public function testLoginWithNoProvisioning() : void {
		$query = $this->login_query();

		// Test bad username.
		$variables = [
			'username' => 'baduser',
			'password' => '12345',
		];

		// Test with no user to match.
		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayHasKey( 'errors', $actual );
		// The error message changes in WP 5.7
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
}
