<?php
/**
 * Tests the refreshToken mutation.
 *
 * @package WPGraphQL\Login\Tests\Integration\GraphQL\Type\Mutation
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Tests\Integration\GraphQL\Type\Mutation;

use PHPUnit\Framework\Attributes\CoversClass;
use WPGraphQL\Login\Auth\TokenManager;
use WPGraphQL\Login\Auth\User;
use WPGraphQL\Login\GraphQL\Type\Mutation\RefreshToken;
use WPGraphQL\Login\Tests\TestCase;
use WPGraphQL\Login\Vendor\Firebase\JWT\JWT;

/**
 * Tests the refreshToken mutation.
 */
#[CoversClass( RefreshToken::class )]
class RefreshTokenTest extends TestCase {
	/**
	 * The administrator user ID.
	 */
	private int $admin;

	/**
	 * The test user ID.
	 */
	private int $test_user;

	/**
	 * The auth token for the test user.
	 */
	private string $auth_token;

	/**
	 * The refresh token for the test user.
	 */
	private string $refresh_token;

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
				'role' => 'subscriber',
			]
		);

		$tokens = $this->generate_user_tokens( $this->test_user );

		$this->auth_token    = $tokens['auth_token'];
		$this->refresh_token = $tokens['refresh_token'];
	}

	/**
	 * {@inheritDoc}
	 */
	protected function tearDown(): void {
		$this->reset_utils_properties();
		$this->clearSchema();

		parent::tearDown();
	}

	/**
	 * Returns the refreshToken mutation.
	 */
	private function query(): string {
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

	/**
	 * Asserts that the refreshToken mutation failed without issuing a new auth token.
	 *
	 * @param array<string,mixed> $actual           The GraphQL response.
	 * @param string              $expected_message The expected debug message.
	 */
	private function assert_refresh_token_failed( array $actual, string $expected_message ): void {
		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertFalse( $actual['data']['refreshToken']['success'] );
		$this->assertNull( $actual['data']['refreshToken']['authToken'] );
		$this->assertNull( $actual['data']['refreshToken']['authTokenExpiration'] );
		$this->assertEquals( $expected_message, $actual['extensions']['debug'][0]['message'] );
	}

	/**
	 * Tests that a refresh token for a user that no longer exists is rejected, since their secret is deleted with them.
	 */
	public function test_refresh_token_fails_for_deleted_user(): void {
		require_once ABSPATH . 'wp-admin/includes/user.php';
		wp_delete_user( $this->test_user );

		$query     = $this->query();
		$variables = [
			'refreshToken' => $this->refresh_token,
		];

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assert_refresh_token_failed( $actual, 'User secret does not match.' );
	}

	/**
	 * Tests that a malformed refresh token is rejected.
	 */
	public function test_refresh_token_fails_with_malformed_token(): void {
		$query = $this->query();

		$variables = [
			'refreshToken' => 'badtoken',
		];

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assert_refresh_token_failed( $actual, 'Wrong number of segments' );
	}

	/**
	 * Tests that a spoofed token that isn't signed with the site secret is rejected.
	 */
	public function test_refresh_token_fails_with_spoofed_token(): void {
		$query = $this->query();

		// Spoof a token for a nonexistent user ID, signed with the admin's user secret.
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

		$this->reset_utils_properties();
		wp_set_current_user( $this->admin );
		$user_secret = TokenManager::get_user_secret( $this->admin, false );
		wp_set_current_user( 0 );

		$signed_token = JWT::encode( $refresh_token_args, $user_secret, 'HS256' );

		$variables = [
			'refreshToken' => $signed_token,
		];

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assert_refresh_token_failed( $actual, 'Signature verification failed' );

		// Test a spoofed token for the test user, as the admin.
		$refresh_token_args['data']['user']['id'] = $this->test_user;

		$signed_token = JWT::encode( $refresh_token_args, $user_secret, 'HS256' );

		$variables = [
			'refreshToken' => $signed_token,
		];

		wp_set_current_user( $this->admin );

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assert_refresh_token_failed( $actual, 'Signature verification failed' );
	}

	/**
	 * Tests that an auth token cannot be used in place of a refresh token.
	 */
	public function test_refresh_token_fails_with_auth_token(): void {
		$query = $this->query();

		$variables = [
			'refreshToken' => $this->auth_token,
		];

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assert_refresh_token_failed( $actual, 'User secret not found in the token.' );

		// Test auth token as test user.
		wp_set_current_user( $this->test_user );

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assert_refresh_token_failed( $actual, 'User secret not found in the token.' );
	}

	/**
	 * Tests that a refresh token is rejected once the user secret is revoked.
	 */
	public function test_refresh_token_fails_when_user_secret_is_revoked(): void {
		$query = $this->query();

		$variables = [
			'refreshToken' => $this->refresh_token,
		];

		// Revoke secret.
		wp_set_current_user( $this->admin );
		TokenManager::revoke_user_secret( $this->test_user, false );
		$this->reset_utils_properties();
		wp_set_current_user( 0 );

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assert_refresh_token_failed( $actual, 'User secret is revoked.' );
	}

	/**
	 * Tests that a refresh token minted from a previous user secret is rejected.
	 */
	public function test_refresh_token_fails_with_token_from_previous_user_secret(): void {
		$query = $this->query();

		$variables = [
			'refreshToken' => $this->refresh_token,
		];

		// Refresh secret.
		wp_set_current_user( $this->admin );
		$this->reset_utils_properties();
		TokenManager::issue_new_user_secret( $this->test_user, false );
		$this->reset_utils_properties();
		wp_set_current_user( 0 );

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assert_refresh_token_failed( $actual, 'User secret does not match.' );

		// Test as admin.
		wp_set_current_user( $this->admin );

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assert_refresh_token_failed( $actual, 'User secret does not match.' );
	}

	/**
	 * Tests that a valid refresh token returns a new auth token and its expiration.
	 */
	public function test_refresh_token_returns_new_auth_token_for_valid_refresh_token(): void {
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
