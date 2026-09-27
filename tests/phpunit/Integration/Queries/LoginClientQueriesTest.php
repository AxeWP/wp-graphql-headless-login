<?php
/**
 * Tests the login client queries.
 *
 * @package Tests\WPGraphQL\Login\Integration\Queries
 */

namespace Tests\WPGraphQL\Login\Integration\Queries;

use Tests\WPGraphQL\Login\TestCase;
use WPGraphQL\Type\WPEnumType;

/**
 * Tests querying for login clients
 */
class LoginClientQueriesTest extends TestCase {
	/**
	 * The provider config settings.
	 *
	 * @var array<string,mixed>
	 */
	public array $client_config = [];

	/**
	 * The administrator user ID.
	 *
	 * @var int
	 */
	public $admin;

	/**
	 * {@inheritDoc}
	 */
	public function setUp(): void {
		parent::setUp();

		$this->reset_utils_properties();
		$this->clear_client_config( 'facebook' );
		$this->clearSchema();

		// Set the provider settings
		$this->client_config = [
			'name'          => 'Facebook',
			'slug'          => 'facebook',
			'order'         => 0,
			'isEnabled'     => true,
			'clientOptions' => [
				'clientId'        => '1234567890',
				'clientSecret'    => 'my-test-secret',
				'redirectUri'     => 'https://example.com/api/auth/facebook/callback',
				'graphAPIVersion' => 'v16.0',
			],
			'loginOptions'  => [
				'linkExistingUsers'      => true,
				'createUserIfNoneExists' => true,
			],
		];

		$this->admin = $this->factory()->user->create(
			[
				'role' => 'administrator',
			]
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function tearDown(): void {
		$this->clear_client_config( 'facebook' );
		$this->clearSchema();

		parent::tearDown();
	}

	/**
	 * Test the `loginClients` query.
	 */
	public function testClientsQuery(): void {
		$query = '
			query LoginClientQuery {
				loginClients {
					authorizationUrl
					clientId
					isEnabled
					name
					order
					provider
					clientOptions {
						... on FacebookClientOptions {
							clientId
							clientSecret
							redirectUri
						}
					}
					loginOptions {
						... on FacebookLoginOptions {
							linkExistingUsers
							createUserIfNoneExists
						}
						useAuthenticationCookie
					}
				}
			}
		';

		// Test with no providers
		$this->reset_utils_properties();
		$this->clear_client_config( 'facebook' );

		$actual = $this->graphql( compact( 'query' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertNull( $actual['data']['loginClients'] );

		// Test with providers
		$this->set_client_config( 'facebook', $this->client_config );

		$actual = $this->graphql( compact( 'query' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertCount( 1, $actual['data']['loginClients'] );
		$this->assertQuerySuccessful(
			$actual,
			[
				$this->expectedNode(
					'loginClients',
					[
						$this->expectedField( 'clientId', $this->client_config['clientOptions']['clientId'] ),
						$this->expectedField( 'isEnabled', $this->client_config['isEnabled'] ),
						$this->expectedField( 'name', $this->client_config['name'] ),
						$this->expectedField( 'order', $this->client_config['order'] ),
						$this->expectedField( 'provider', WPEnumType::get_safe_name( $this->client_config['slug'] ) ),
						// These should be null because the user isnt Authenticated
						$this->expectedField( 'clientOptions', self::IS_NULL ),
						$this->expectedField( 'loginOptions', self::IS_NULL ),
					]
				),
			]
		);
		$this->assertStringStartsWith( 'https://www.facebook.com/v16.0/dialog/oauth', $actual['data']['loginClients'][0]['authorizationUrl'] );
		// Check the authorization url has the correct query params
		$auth_url = parse_url( $actual['data']['loginClients'][0]['authorizationUrl'] );
		parse_str( $auth_url['query'], $query_params );
		$this->assertEquals( $this->client_config['clientOptions']['clientId'], $query_params['client_id'] );
		$this->assertEquals(
			$this->client_config['clientOptions']['redirectUri'],
			esc_url( $query_params['redirect_uri'] )
		);
		$this->assertArrayHasKey( 'state', $query_params );
		$this->assertArrayHasKey( 'scope', $query_params );
		$this->assertArrayHasKey( 'response_type', $query_params );
	}

	public function testClientsQueryWithAuthenticatedUser(): void {
		$query = '
			query LoginClientQuery {
				loginClients {
					clientOptions {
						... on FacebookClientOptions {
							clientId
							clientSecret
							redirectUri
						}
					}
					loginOptions {
						... on FacebookLoginOptions {
							linkExistingUsers
							createUserIfNoneExists
						}
						useAuthenticationCookie
					}
				}
			}
		';

		$this->set_client_config( 'facebook', $this->client_config );
		wp_set_current_user( $this->admin );

		$actual = $this->graphql( compact( 'query' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertCount( 1, $actual['data']['loginClients'] );
		$this->assertQuerySuccessful(
			$actual,
			[
				$this->expectedNode(
					'loginClients',
					[
						$this->expectedObject(
							'clientOptions',
							[
								$this->expectedField( 'clientId', $this->client_config['clientOptions']['clientId'] ),
								$this->expectedField( 'clientSecret', $this->client_config['clientOptions']['clientSecret'] ),
								$this->expectedField( 'redirectUri', $this->client_config['clientOptions']['redirectUri'] ),
							]
						),
						$this->expectedObject(
							'loginOptions',
							[
								$this->expectedField( 'linkExistingUsers', $this->client_config['loginOptions']['linkExistingUsers'] ),
								$this->expectedField( 'createUserIfNoneExists', $this->client_config['loginOptions']['createUserIfNoneExists'] ),
							]
						),
					],
					0
				),
			]
		);
	}

	public function testClientQuery(): void {
		$query = '
			query LoginClientQuery( $provider: LoginProviderEnum! ) {
				loginClient( provider: $provider ) {
					authorizationUrl
					clientId
					isEnabled
					name
					order
					provider
					clientOptions {
						... on FacebookClientOptions {
							clientId
							clientSecret
							redirectUri
						}
					}
					loginOptions {
						... on FacebookLoginOptions {
							linkExistingUsers
							createUserIfNoneExists
						}
						useAuthenticationCookie
					}
				}
			}
		';

		$variables = [
			'provider' => 'FACEBOOK',
		];

		// Test with no providers
		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayHasKey( 'errors', $actual );

		// Compat for WPGraphQL v1.x.
		$debug_message = $actual['errors'][0]['extensions']['debugMessage'] ?? $actual['errors'][0]['debugMessage'];
		$this->assertEquals( 'Provider facebook is not enabled.', $debug_message );

		// Test with providers
		$this->set_client_config( 'facebook', $this->client_config );

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertQuerySuccessful(
			$actual,
			[
				$this->expectedObject(
					'loginClient',
					[
						$this->expectedField( 'clientId', $this->client_config['clientOptions']['clientId'] ),
						$this->expectedField( 'isEnabled', $this->client_config['isEnabled'] ),
						$this->expectedField( 'name', $this->client_config['name'] ),
						$this->expectedField( 'order', $this->client_config['order'] ),
						$this->expectedField( 'provider', WPEnumType::get_safe_name( $this->client_config['slug'] ) ),
						// These should be null because the user isnt Authenticated
						$this->expectedField( 'clientOptions', self::IS_NULL ),
						$this->expectedField( 'loginOptions', self::IS_NULL ),
					]
				),
			]
		);
	}

	public function testClientQueryWithAuthenticatedUser(): void {
		$query = '
			query LoginClientQuery( $provider: LoginProviderEnum! ) {
				loginClient( provider: $provider ) {
					clientOptions {
						... on FacebookClientOptions {
							clientId
							clientSecret
							redirectUri
						}
					}
					loginOptions {
						... on FacebookLoginOptions {
							linkExistingUsers
							createUserIfNoneExists
						}
						useAuthenticationCookie
					}
				}
			}
		';

		$variables = [
			'provider' => 'FACEBOOK',
		];

		$this->set_client_config( 'facebook', $this->client_config );
		wp_set_current_user( $this->admin );

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertQuerySuccessful(
			$actual,
			[
				$this->expectedObject(
					'loginClient',
					[
						$this->expectedObject(
							'clientOptions',
							[
								$this->expectedField( 'clientId', $this->client_config['clientOptions']['clientId'] ),
								$this->expectedField( 'clientSecret', $this->client_config['clientOptions']['clientSecret'] ),
								$this->expectedField( 'redirectUri', $this->client_config['clientOptions']['redirectUri'] ),
							]
						),
						$this->expectedObject(
							'loginOptions',
							[
								$this->expectedField( 'linkExistingUsers', $this->client_config['loginOptions']['linkExistingUsers'] ),
								$this->expectedField( 'createUserIfNoneExists', $this->client_config['loginOptions']['createUserIfNoneExists'] ),
							]
						),
					]
				),
			]
		);
	}
}
