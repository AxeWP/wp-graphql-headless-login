<?php
/**
 * Tests authenticated GraphQL requests end-to-end.
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

	/**
	 * Tests that invalid auth headers return public data along with a debug message.
	 */
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

		$execution = $this->capture_response_headers(
			function () use ( $query ) {
				return $this->graphql( [ 'query' => $query ] );
			}
		);

		$response = $execution['response'];
		$headers  = $execution['headers'];

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

	/**
	 * Tests that a request without auth headers returns public data and no tokens.
	 */
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

		$execution = $this->capture_response_headers(
			function () use ( $query ) {
				return $this->graphql( [ 'query' => $query ] );
			}
		);

		$response = $execution['response'];
		$headers  = $execution['headers'];

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
	 * Tests that a request from an authorized origin refreshes the tokens.
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
				'customHeaders'                  => [ 'X-Custom-Header' ],
			]
		);
		$this->reset_utils_properties();
		wp_set_current_user( $user_id );

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

		$unauthorized_headers = [];
		$filter               = static function ( array $headers ) use ( &$unauthorized_headers ): array {
			$unauthorized_headers = $headers;
			return $headers;
		};

		add_filter( 'graphql_response_headers_to_send', $filter );

		try {
			$this->graphql( [ 'query' => $query ] );
			$this->fail( 'Expected an unauthorized origin error.' );
		} catch ( UserError $error ) {
			$this->assertSame( 'Unauthorized request origin.', $error->getMessage() );
		} finally {
			call_user_func( 'remove_filter', 'graphql_response_headers_to_send', $filter );
		}

		$this->assertArrayNotHasKey( 'X-WPGraphQL-Login-Token', $unauthorized_headers );
		$this->assertArrayNotHasKey( 'X-WPGraphQL-Login-Refresh-Token', $unauthorized_headers );

		$this->reset_utils_properties();
		$_SERVER['HTTP_ORIGIN'] = site_url();

		$response = $this->graphql( [ 'query' => $query ] );
		$this->assertArrayNotHasKey( 'errors', $response );
		$this->assertSame( $user_id, $response['data']['viewer']['databaseId'] );
		$this->assertSame( 'testuser', $response['data']['viewer']['username'] );
		$this->assertNotEmpty( $response['data']['viewer']['auth']['authToken'] );
		$this->assertNotEmpty( $response['data']['viewer']['auth']['authTokenExpiration'] );
		$this->assertNotEmpty( $response['data']['viewer']['auth']['refreshToken'] );
		$this->assertNotEmpty( $response['data']['viewer']['auth']['refreshTokenExpiration'] );
		$this->assertFalse( $response['data']['viewer']['auth']['isUserSecretRevoked'] );
		$this->assertNotEmpty( $response['data']['viewer']['auth']['userSecret'] );
	}

	/**
	 * Runs the callback, returning its response along with the response headers.
	 */
	private function capture_response_headers( callable $callback ): array {
		$captured_headers = [];
		$filter           = static function ( array $headers ) use ( &$captured_headers ): array {
			$captured_headers = $headers;
			return $headers;
		};

		add_filter( 'graphql_response_headers_to_send', $filter );

		try {
			$response = $callback();
		} finally {
			call_user_func( 'remove_filter', 'graphql_response_headers_to_send', $filter );
		}

		return [
			'response' => $response,
			'headers'  => $captured_headers,
		];
	}

	/**
	 * Returns the debug messages from a GraphQL response.
	 */
	private function get_debug_messages( array $response ): array {
		return array_values( call_user_func( 'wp_list_pluck', $response['extensions']['debug'] ?? [], 'message' ) );
	}
}
