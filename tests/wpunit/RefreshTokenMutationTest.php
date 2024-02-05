<?php

use WPGraphQL\Login\Auth\TokenManager;
use WPGraphQL\Login\Auth\User;
use WPGraphQL\Login\Vendor\Firebase\JWT\JWT;

/**
 * Tests access functons
 */
class RefreshTokenMutationTest extends \Tests\WPGraphQL\TestCase\WPGraphQLTestCase {
	public $tester;
	public $admin;
	public $test_user;
	public $auth_token;
	public $refresh_token;

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
				'role' => 'subscriber',
			]
		);

		$test_user_obj = $this->factory()->user->get_object_by_id( $this->test_user );

		// Add Auth and Refresh tokens.
		wp_set_current_user( $this->test_user );
		TokenManager::issue_new_user_secret( $this->test_user );

		$this->tester->reset_utils_properties();

		$this->auth_token = TokenManager::get_auth_token( $test_user_obj, false );

		$this->tester->reset_utils_properties();

		$this->refresh_token = TokenManager::get_refresh_token( $test_user_obj, false );
		wp_set_current_user( 0 );

		$this->tester->reset_utils_properties();
	}

	/**
	 * {@inheritDoc}
	 */
	public function tearDown(): void {
		$this->tester->reset_utils_properties();
		$this->clearSchema();
		parent::tearDown();
	}

	public function query() : string {
		return '
			mutation RefreshToken( $refreshToken: String! ) {
				refreshToken( input: { refreshToken: $refreshToken } ) {
					authToken
					authTokenExpiration
					success
				}
			}
		';
	}

	public function testWithBadToken() : void {
		$query = $this->query();

		// Test bad token
		$variables = [
			'refreshToken' => 'badtoken',
		];

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertFalse( $actual['data']['refreshToken']['success'] );
		$this->assertNull( $actual['data']['refreshToken']['authToken'] );
		$this->assertNull( $actual['data']['refreshToken']['authTokenExpiration'] );
		$this->assertEquals( 'Wrong number of segments', $actual['extensions']['debug'][0]['message'] );
	}

	public function testWithSpoofedToken() : void {
		$query = $this->query();

		// Spoof token with bad user ID.

		$refresh_token_args = [
			'iss'  => get_bloginfo( 'url' ),
			'iat'  => time(),
			'nbf'  => time(),
			'exp'  => time() + ( DAY_IN_SECONDS * 365 ),
			'data' => [
				'user' => [
					'id' => 99999,
				],
			],
		];

		$this->tester->reset_utils_properties();
		wp_set_current_user( $this->admin );
		$user_secret = TokenManager::get_user_secret( $this->admin, false );
		wp_set_current_user( 0 );

		$data['user']['user_secret'] = $user_secret;

		JWT::$leeway  = 60;
		$signed_token = JWT::encode( $refresh_token_args, $user_secret, 'HS256' );

		// Test bad token
		$variables = [
			'refreshToken' => $signed_token,
		];

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertFalse( $actual['data']['refreshToken']['success'] );
		$this->assertNull( $actual['data']['refreshToken']['authToken'] );
		$this->assertNull( $actual['data']['refreshToken']['authTokenExpiration'] );
		$this->assertEquals( 'Signature verification failed', $actual['extensions']['debug'][0]['message'] );

		// Test different user as admin

		$refresh_token_args['data']['user']['id'] = $this->test_user;

		$signed_token = JWT::encode( $refresh_token_args, $user_secret, 'HS256' );

		$variables = [
			'refreshToken' => $signed_token,
		];

		wp_set_current_user( $this->admin );

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertFalse( $actual['data']['refreshToken']['success'] );
		$this->assertNull( $actual['data']['refreshToken']['authToken'] );
		$this->assertNull( $actual['data']['refreshToken']['authTokenExpiration'] );
		$this->assertEquals( 'Signature verification failed', $actual['extensions']['debug'][0]['message'] );
	}

	public function testWithAuthToken() : void {
		$query = $this->query();

		// Test auth token.
		$variables = [
			'refreshToken' => $this->auth_token,
		];

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertFalse( $actual['data']['refreshToken']['success'] );
		$this->assertNull( $actual['data']['refreshToken']['authToken'] );
		$this->assertNull( $actual['data']['refreshToken']['authTokenExpiration'] );
		$this->assertEquals( 'User secret not found in the token.', $actual['extensions']['debug'][0]['message'] );

		// Test auth token as test user
		wp_set_current_user( $this->test_user );

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertFalse( $actual['data']['refreshToken']['success'] );
		$this->assertNull( $actual['data']['refreshToken']['authToken'] );
		$this->assertNull( $actual['data']['refreshToken']['authTokenExpiration'] );
		$this->assertEquals( 'User secret not found in the token.', $actual['extensions']['debug'][0]['message'] );
	}

	public function testWithSecretRevoked() : void {
		$query = $this->query();

		$variables = [
			'refreshToken' => $this->refresh_token,
		];

		// Revoke secret.
		wp_set_current_user( $this->admin );
		TokenManager::revoke_user_secret( $this->test_user, false );
		$this->tester->reset_utils_properties();
		wp_set_current_user( 0 );

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertFalse( $actual['data']['refreshToken']['success'] );
		$this->assertNull( $actual['data']['refreshToken']['authToken'] );
		$this->assertNull( $actual['data']['refreshToken']['authTokenExpiration'] );
		$this->assertEquals( 'User secret is revoked.', $actual['extensions']['debug'][0]['message'] );
	}

	public function testWithOldSecret() : void {
		$query = $this->query();

		$variables = [
			'refreshToken' => $this->refresh_token,
		];

		// Refresh secret.
		wp_set_current_user( $this->admin );
		$this->tester->reset_utils_properties();
		TokenManager::issue_new_user_secret( $this->test_user, false );
		$this->tester->reset_utils_properties();
		wp_set_current_user( 0 );

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertFalse( $actual['data']['refreshToken']['success'] );
		$this->assertNull( $actual['data']['refreshToken']['authToken'] );
		$this->assertNull( $actual['data']['refreshToken']['authTokenExpiration'] );
		$this->assertEquals( 'User secret does not match.', $actual['extensions']['debug'][0]['message'] );

		// Test as admin
		wp_set_current_user( $this->admin );

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertFalse( $actual['data']['refreshToken']['success'] );
		$this->assertNull( $actual['data']['refreshToken']['authToken'] );
		$this->assertNull( $actual['data']['refreshToken']['authTokenExpiration'] );
		$this->assertEquals( 'User secret does not match.', $actual['extensions']['debug'][0]['message'] );
	}

	public function testWithValidRefreshToken() : void {
		$query = $this->query();

		$variables = [
			'refreshToken' => $this->refresh_token,
		];

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertTrue( $actual['data']['refreshToken']['success'] );
		$this->assertNotNull( $actual['data']['refreshToken']['authToken'] );

		$expected_expiration = User::get_auth_token_expiration( $this->test_user );
		$this->assertEquals( $expected_expiration, $actual['data']['refreshToken']['authTokenExpiration'] );
	}
}
