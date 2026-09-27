<?php
/**
 * Tests authenticated GraphQL requests.
 *
 * @package Tests\WPGraphQL\Login\Integration\Functional
 */

namespace Tests\WPGraphQL\Login\Integration\Functional;

use GraphQL\Error\UserError;
use Tests\WPGraphQL\Login\TestCase;
use WPGraphQL\Login\Admin\Settings\AccessControlSettings;

/**
 * Tests token authentication over a full GraphQL request.
 */
class AuthenticatedQueryTest extends TestCase {
	/**
	 * {@inheritDoc}
	 */
	public function setUp(): void {
		parent::setUp();

		update_option( 'graphql_general_settings', [ 'debug_mode_enabled' => 'on' ] );
		$this->clearSchema();
	}

	/**
	 * {@inheritDoc}
	 */
	public function tearDown(): void {
		unset( $_SERVER['HTTP_AUTHORIZATION'], $_SERVER['HTTP_ORIGIN'] );
		delete_option( AccessControlSettings::get_slug() );
		$this->reset_utils_properties();

		parent::tearDown();
	}

	public function test_query_with_invalid_headers_returns_public_data_and_debug_message(): void {
		$this->factory()->user->create(
			[
				'role'       => 'administrator',
				'user_login' => 'testuser',
				'user_pass'  => 'testpass',
			]
		);

		$this->factory()->post->create(
			[
				'post_title'   => 'Test Post',
				'post_type'    => 'post',
				'post_status'  => 'publish',
				'post_content' => 'Post Content',
			]
		);

		$query = '
			query {
				posts {
					edges {
						node {
							id
							title
							link
							date
						}
					}
				}
				viewer {
					databaseId
					username
					auth {
						authToken
						authTokenExpiration
						refreshToken
						refreshTokenExpiration
						isUserSecretRevoked
						userSecret
					}
				}
			}
		';

		$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer invalid-auth-token';

		$response = $this->graphql( [ 'query' => $query ] );
		$headers  = apply_filters( 'graphql_response_headers_to_send', [] );

		$this->assertSame( 403, apply_filters( 'graphql_response_status_code', 200 ) );
		$this->assertArrayNotHasKey( 'X-WPGraphQL-Login-Token', $headers );
		$this->assertArrayNotHasKey( 'X-WPGraphQL-Login-Refresh-Token', $headers );
		$this->assertSame( [ 'invalid-secret-key | Wrong number of segments' ], $this->get_debug_messages( $response ) );
		$this->assertArrayHasKey( 'data', $response );
		$this->assertNull( $response['data']['viewer'] );
		$this->assertNotEmpty( $response['data']['posts']['edges'][0]['node']['id'] );
		$this->assertNotEmpty( $response['data']['posts']['edges'][0]['node']['title'] );
		$this->assertNotEmpty( $response['data']['posts']['edges'][0]['node']['link'] );
		$this->assertNotEmpty( $response['data']['posts']['edges'][0]['node']['date'] );
	}

	public function test_query_without_headers_returns_public_data_without_tokens(): void {
		$this->factory()->post->create(
			[
				'post_title'   => 'Test Post',
				'post_type'    => 'post',
				'post_status'  => 'publish',
				'post_content' => 'Post Content',
			]
		);

		$query = '
			query {
				posts {
					edges {
						node {
							id
							title
							link
							date
						}
					}
				}
			}
		';

		$response = $this->graphql( [ 'query' => $query ] );
		$headers  = apply_filters( 'graphql_response_headers_to_send', [] );

		$this->assertArrayNotHasKey( 'X-WPGraphQL-Login-Token', $headers );
		$this->assertArrayNotHasKey( 'X-WPGraphQL-Login-Refresh-Token', $headers );
		$this->assertArrayNotHasKey( 'errors', $response );
		$this->assertSame( [], $this->get_debug_messages( $response ) );
		$this->assertNotEmpty( $response['data']['posts']['edges'][0]['node']['id'] );
		$this->assertNotEmpty( $response['data']['posts']['edges'][0]['node']['title'] );
		$this->assertNotEmpty( $response['data']['posts']['edges'][0]['node']['link'] );
		$this->assertNotEmpty( $response['data']['posts']['edges'][0]['node']['date'] );
	}

	/**
	 * Tests that a token-authenticated request is only allowed from an authorized origin, which gets refreshed tokens.
	 */
	public function test_query_with_authorized_origin_refreshes_tokens(): void {
		$user_id = $this->factory()->user->create(
			[
				'role'       => 'administrator',
				'user_login' => 'testuser',
				'user_pass'  => 'testpass',
			]
		);

		update_option(
			AccessControlSettings::get_slug(),
			[
				'shouldBlockUnauthorizedDomains' => true,
				'hasSiteAddressInOrigin'         => true,
				'additionalAuthorizedDomains'    => [ 'https://example.com' ],
				'customHeaders'                  => [ 'X-Custom-Header' ],
			]
		);
		$this->reset_utils_properties();

		$tokens = $this->generate_user_tokens( $user_id );

		// Authenticate from the auth token alone.
		$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $tokens['auth_token'];
		$GLOBALS['current_user']       = null;

		$query = '
			query {
				viewer {
					databaseId
					username
					auth {
						authToken
						authTokenExpiration
						refreshToken
						refreshTokenExpiration
						isUserSecretRevoked
						userSecret
					}
				}
			}
		';

		try {
			$this->graphql( [ 'query' => $query ] );
			$this->fail( 'Expected an unauthorized origin error.' );
		} catch ( UserError $error ) {
			$this->assertSame( 'Unauthorized request origin.', $error->getMessage() );
		}

		$headers = apply_filters( 'graphql_response_headers_to_send', [] );

		$this->assertSame( 403, apply_filters( 'graphql_response_status_code', 200 ) );
		$this->assertArrayNotHasKey( 'X-WPGraphQL-Login-Token', $headers );
		$this->assertArrayNotHasKey( 'X-WPGraphQL-Login-Refresh-Token', $headers );

		$this->reset_utils_properties();
		$_SERVER['HTTP_ORIGIN'] = 'https://example.com';

		$response = $this->graphql( [ 'query' => $query ] );
		$headers  = apply_filters( 'graphql_response_headers_to_send', [] );

		$this->assertNotEmpty( $headers['X-WPGraphQL-Login-Token'] ?? null );
		$this->assertNotEmpty( $headers['X-WPGraphQL-Login-Refresh-Token'] ?? null );

		$this->assertArrayNotHasKey( 'errors', $response );
		$this->assertSame( [], $this->get_debug_messages( $response ) );
		$this->assertSame( $user_id, $response['data']['viewer']['databaseId'] );
		$this->assertSame( 'testuser', $response['data']['viewer']['username'] );
		$this->assertNotEmpty( $response['data']['viewer']['auth']['authToken'] );
		$this->assertNotEmpty( $response['data']['viewer']['auth']['authTokenExpiration'] );
		$this->assertNotEmpty( $response['data']['viewer']['auth']['refreshToken'] );
		$this->assertNotEmpty( $response['data']['viewer']['auth']['refreshTokenExpiration'] );
		$this->assertFalse( $response['data']['viewer']['auth']['isUserSecretRevoked'] );
		$this->assertNotEmpty( $response['data']['viewer']['auth']['userSecret'] );
	}
}
