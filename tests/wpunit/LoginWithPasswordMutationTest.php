<?php

/**
 * Tests access functons
 */
class LoginWithPasswordMutationTest extends \Tests\WPGraphQL\TestCase\WPGraphQLTestCase {
	public $tester;
	public $admin;
	public $test_user;
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
				'user_login' => 'test_user',
				'user_pass'  => 'test_password',
				'role'       => 'subscriber',
			]
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function tearDown(): void {
		$this->clearSchema();
		parent::tearDown();
	}

	public function query() : string {
		return '
			mutation LoginWithPassword( $username: String!, $password: String! ) {
				loginWithPassword(
					input: {username: $username, password: $password}
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
						username
					}
				}
			}
		';
	}

	public function testMutation() : void {
		$query = $this->query();

		// Test bad username
		$variables = [
			'username' => 'baduser',
			'password' => '12345',
		];

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayHasKey( 'errors', $actual );
		// The error message changes in WP 5.7
		$this->assertNotEmpty( $actual['errors'][0]['message'] );

		// Test with bad password.
		$variables['username'] = 'test_user';

		$actual = $this->graphql( compact( 'query', 'variables' ) );
		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertEquals( 'Error: The password you entered for the username test_user is incorrect. Lost your password?', $actual['errors'][0]['message'] );

		// Test with user already logged in.
		wp_set_current_user( $this->test_user );

		$actual = $this->graphql( compact( 'query', 'variables' ) );
		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertEquals( 'You are already logged in.', $actual['errors'][0]['message'] );

		// Test with correct credentials.
		wp_set_current_user( 0 );

		$variables['password'] = 'test_password';

		$actual = $this->graphql( compact( 'query', 'variables' ) );
		$this->assertArrayNotHasKey( 'errors', $actual );

		$this->assertQuerySuccessful(
			$actual,
			[
				$this->expectedObject(
					'loginWithPassword',
					[
						$this->expectedField( 'authToken', self::NOT_FALSY ),
						$this->expectedField( 'authTokenExpiration', self::NOT_FALSY ),
						$this->expectedField( 'refreshToken', self::NOT_FALSY ),
						$this->expectedField( 'refreshTokenExpiration', self::NOT_FALSY ),
						$this->expectedObject(
							'user',
							[
								$this->expectedField( 'databaseId', $this->test_user ),
								$this->expectedField( 'username', 'test_user' ),
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

	public function testDisabledMutation() : void {
		// Disable the mutation.
		update_option( 'wpgraphql_login_settings_enable_password_mutation', false );
		$this->tester->reset_utils_properties();
		$this->clearSchema();


		$query = $this->query();

		// Test with correct credentials.
		wp_set_current_user( 0 );

		$variables = [
			'username' => 'test_user',
			'password' => 'test_password',
		];


		$actual = $this->graphql( compact( 'query', 'variables' ) );
		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertEquals( 'Cannot query field "loginWithPassword" on type "RootMutation".', $actual['errors'][0]['message'] );

		// Cleanup.
		delete_option( 'wpgraphql_login_settings_enable_password_mutation' );
		$this->tester->reset_utils_properties();
	}
}
