<?php
/**
 * Tests the refreshToken mutation.
 *
 * @package Tests\WPGraphQL\Login\Integration\Auth
 */

namespace Tests\WPGraphQL\Login\Integration\Auth;

use Tests\WPGraphQL\Login\TestCase;
use WPGraphQL\Login\Auth\TokenManager;
use WPGraphQL\Login\Auth\User;
use WPGraphQL\Login\Vendor\Firebase\JWT\JWT;

/**
 * Tests the refreshToken mutation.
 */
class RefreshTokenMutationTest extends TestCase {
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
	 * The auth token for the test user.
	 *
	 * @var string
	 */
	public $auth_token;

	/**
	 * The refresh token for the test user.
	 *
	 * @var string
	 */
	public $refresh_token;

	/**
	 * The `iss` values of the tokens minted during the test.
	 */
	private array $token_issuers = [];

	/**
	 * Returns the `iss` claim from the given token.
	 */
	private function getTokenIssuer( string $token ): ?string {
		$parts = explode( '.', $token );

		if ( 3 !== count( $parts ) ) {
			return null;
		}

		$payload = json_decode( (string) base64_decode( strtr( $parts[1], '-_', '+/' ) ) );

		return isset( $payload->iss ) && is_string( $payload->iss ) ? $payload->iss : null;
	}

	/**
	 * Adds the test site URLs and minted token issuers to the allowed `iss` domains.
	 */
	public function filterAllowedIssDomains( array $allowed_domains ): array {
		$home_url = (string) call_user_func( 'home_url' );

		return array_values(
			array_filter(
				array_unique(
					array_merge(
						$allowed_domains,
						[
							$home_url,
							$this->getBlogUrl(),
							str_replace( 'http://', 'https://', $home_url ),
							str_replace( 'https://', 'http://', $home_url ),
							...$this->token_issuers,
						]
					)
				)
			)
		);
	}

	/**
	 * Wrapper for `get_bloginfo( 'url' )`.
	 */
	private function getBlogUrl(): string {
		return (string) call_user_func( 'get_bloginfo', 'url' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function setUp(): void {
		parent::setUp();

		add_filter( 'graphql_login_iss_allowed_domains', [ $this, 'filterAllowedIssDomains' ] );

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
		$this->token_issuers = array_values(
			array_filter(
				[
					$this->getTokenIssuer( $this->auth_token ),
					$this->getTokenIssuer( $this->refresh_token ),
				]
			)
		);

		unset( $_SERVER['HTTP_AUTHORIZATION'] );
	}

	/**
	 * {@inheritDoc}
	 */
	public function tearDown(): void {
		unset( $_SERVER['HTTP_AUTHORIZATION'] );
		call_user_func( 'remove_filter', 'graphql_login_iss_allowed_domains', [ $this, 'filterAllowedIssDomains' ] );
		$this->reset_utils_properties();
		$this->clearSchema();

		parent::tearDown();
	}

	/**
	 * The `refreshToken` mutation.
	 */
	public function query(): string {
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
	 * Tests the mutation with a malformed refresh token.
	 */
	public function testWithBadToken(): void {
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

	/**
	 * Tests the mutation with a token signed for a nonexistent user.
	 */
	public function testWithSpoofedToken(): void {
		$query = $this->query();

		// Spoof token with bad user ID.

		$refresh_token_args = [
			'iss'  => $this->getBlogUrl(),
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

	/**
	 * Tests the mutation with an auth token instead of a refresh token.
	 */
	public function testWithAuthToken(): void {
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

	/**
	 * Tests the mutation when the user secret has been revoked.
	 */
	public function testWithSecretRevoked(): void {
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

		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertFalse( $actual['data']['refreshToken']['success'] );
		$this->assertNull( $actual['data']['refreshToken']['authToken'] );
		$this->assertNull( $actual['data']['refreshToken']['authTokenExpiration'] );
		$this->assertEquals( 'User secret is revoked.', $actual['extensions']['debug'][0]['message'] );
	}

	/**
	 * Tests the mutation with a token minted from a previous user secret.
	 */
	public function testWithOldSecret(): void {
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

	/**
	 * Tests the mutation with a valid refresh token.
	 */
	public function testWithValidRefreshToken(): void {
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
