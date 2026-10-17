<?php
/**
 * Base test case for the OAuth2 provider configs.
 *
 * @package WPGraphQL\Login\Tests\Integration\Auth\ProviderConfig\OAuth2
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Tests\Integration\Auth\ProviderConfig\OAuth2;

use WPGraphQL\Login\Auth\ProviderConfig\OAuth2\OAuth2Config;
use WPGraphQL\Login\Auth\User;
use WPGraphQL\Login\Tests\TestCase;

/**
 * Tests the login and linkUserIdentity mutations for an OAuth2 provider.
 *
 * Subclasses swap in a provider config fixture whose provider returns canned resource owner details
 * through a mocked HTTP client, and supply whatever differs between the providers.
 */
abstract class OAuth2ConfigTestCase extends TestCase {
	/**
	 * The resource owner ID returned by the mocked providers.
	 */
	protected const IDENTITY_ID = '12345';

	/**
	 * The ID of the existing subscriber.
	 */
	protected int $test_user;

	/**
	 * Returns the provider slug.
	 */
	abstract protected function get_provider_slug(): string;

	/**
	 * Returns the provider's `LoginProviderEnum` value.
	 */
	abstract protected function get_provider_enum(): string;

	/**
	 * Returns the provider-specific client options, merged over the shared client credentials.
	 *
	 * @return array<string,mixed>
	 */
	abstract protected function get_client_options(): array;

	/**
	 * Creates the provider config fixture that uses the mocked provider.
	 */
	abstract protected function create_provider_config(): OAuth2Config;

	/**
	 * Returns the user fields that the mocked resource owner details map to, keyed by their GraphQL field name.
	 *
	 * @return array{email?:string,firstName?:string,lastName?:string,username?:string}
	 */
	abstract protected function get_provider_user_fields(): array;

	/**
	 * {@inheritDoc}
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->test_user = $this->factory()->user->create( [ 'role' => 'subscriber' ] );

		$this->set_provider_settings();

		add_filter(
			'graphql_login_provider_config_instances',
			function ( array $providers ): array {
				$providers[ $this->get_provider_slug() ] = $this->create_provider_config();

				return $providers;
			}
		);
		$this->clearSchema();
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
	 * Tests that an unlinked identity can't log in without provisioning, and that a linked one logs in as its user.
	 */
	public function test_login_with_no_provisioning(): void {
		$actual = $this->login();

		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertSame( 'The user could not be logged in.', $actual['errors'][0]['message'] );

		User::link_user_identity( $this->test_user, $this->get_provider_slug(), self::IDENTITY_ID );

		$actual = $this->login();

		$this->assert_logged_in( $actual, [ 'databaseId' => $this->test_user ] );
		$this->assert_user_fields_not_overwritten( $actual, $this->get_provider_user_fields() );
	}

	/**
	 * Tests that `linkExistingUsers` logs in the existing user whose email address matches the provider's.
	 */
	public function test_login_with_link_existing_users(): void {
		$this->set_provider_settings( [ 'linkExistingUsers' => true ] );

		$provider_fields = $this->get_provider_user_fields();

		// Test with no user to match.
		$actual = $this->login();

		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertSame( 'The user could not be logged in.', $actual['errors'][0]['message'] );

		// Test with a user to match.
		wp_update_user(
			[
				'ID'         => $this->test_user,
				'user_email' => $provider_fields['email'],
			]
		);

		$actual = $this->login();

		$this->assert_logged_in(
			$actual,
			[
				'databaseId' => $this->test_user,
				'email'      => $provider_fields['email'],
			]
		);

		unset( $provider_fields['email'] );
		$this->assert_user_fields_not_overwritten( $actual, $provider_fields );
	}

	/**
	 * Tests that `createUserIfNoneExists` creates a new user from the provider's details, unless the email address is taken.
	 */
	public function test_login_with_create_user(): void {
		$this->set_provider_settings( [ 'createUserIfNoneExists' => true ] );

		$provider_fields = $this->get_provider_user_fields();

		// Only providers that return an email address can collide with an existing user.
		if ( isset( $provider_fields['email'] ) ) {
			wp_update_user(
				[
					'ID'         => $this->test_user,
					'user_email' => $provider_fields['email'],
				]
			);

			$actual = $this->login();

			$this->assertArrayHasKey( 'errors', $actual );
			$this->assertSame( 'Sorry, that email address is already used!', $actual['errors'][0]['message'] );

			wp_update_user(
				[
					'ID'         => $this->test_user,
					'user_email' => 'some_other_email@email.com',
				]
			);
		}

		$actual = $this->login();

		$this->assert_logged_in( $actual, $provider_fields );

		// A new user will have a new database ID.
		$this->assertNotEquals( $this->test_user, $actual['data']['login']['user']['databaseId'] );
	}

	/**
	 * Tests that linking an identity requires being logged in as that user.
	 */
	public function test_link_user_identity_with_no_permissions(): void {
		// Test logged out.
		$actual = $this->link_user_identity();

		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertSame( 'You must be logged in to link your identity.', $actual['errors'][0]['message'] );

		// Test with a different user.
		wp_set_current_user( $this->factory()->user->create( [ 'role' => 'administrator' ] ) );

		$actual = $this->link_user_identity();

		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertSame( 'You must be logged in as the user to link your identity.', $actual['errors'][0]['message'] );
	}

	/**
	 * Tests linking an identity that is already linked to the user.
	 */
	public function test_link_user_identity_with_existing_identity(): void {
		User::link_user_identity( $this->test_user, $this->get_provider_slug(), self::IDENTITY_ID );
		wp_set_current_user( $this->test_user );

		$actual = $this->link_user_identity();

		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertSame( 'This identity is already linked to your account.', $actual['errors'][0]['message'] );
	}

	/**
	 * Tests linking an identity that is already linked to another user.
	 */
	public function test_link_user_identity_with_conflicting_identity(): void {
		User::link_user_identity( $this->factory()->user->create(), $this->get_provider_slug(), self::IDENTITY_ID );
		wp_set_current_user( $this->test_user );

		$actual = $this->link_user_identity();

		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertSame( 'This identity is already linked to another account.', $actual['errors'][0]['message'] );
	}

	/**
	 * Tests that a logged-in user can link the provider identity to their account.
	 */
	public function test_link_user_identity(): void {
		wp_set_current_user( $this->test_user );

		$actual = $this->link_user_identity();

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
								$this->expectedObject( 'auth', [ $this->expected_linked_identity() ] ),
							]
						),
					]
				),
			]
		);
	}

	/**
	 * Saves the settings that enable the provider.
	 *
	 * @param array<string,bool> $login_options The login options, merged over the defaults.
	 */
	protected function set_provider_settings( array $login_options = [] ): void {
		$this->set_client_config(
			$this->get_provider_slug(),
			[
				'slug'          => $this->get_provider_slug(),
				'isEnabled'     => true,
				'clientOptions' => array_merge(
					[
						'clientId'     => 'mock_client_id',
						'clientSecret' => 'mock_client_secret',
						'redirectUri'  => 'mock_redirect_uri',
					],
					$this->get_client_options()
				),
				'loginOptions'  => array_merge(
					[
						'linkExistingUsers'      => false,
						'createUserIfNoneExists' => false,
					],
					$login_options
				),
			]
		);
	}

	/**
	 * Returns the mutation input for a successful authorization code response from the provider.
	 *
	 * @return array{provider:string,oauthResponse:array<string,string>}
	 */
	protected function get_oauth_input(): array {
		return [
			'provider'      => $this->get_provider_enum(),
			'oauthResponse' => [
				'code' => 'mock_authorization_code',
			],
		];
	}

	/**
	 * Runs the login mutation.
	 *
	 * @param ?array<string,mixed> $input The mutation input. Defaults to a successful authorization code response.
	 *
	 * @return array<string,mixed>
	 */
	protected function login( ?array $input = null ): array {
		$query = '
			mutation Login( $input: LoginInput! ) {
				login( input: $input ) {
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
						email
						firstName
						lastName
						username
					}
				}
			}
		';

		$variables = [
			'input' => $input ?? $this->get_oauth_input(),
		];

		return $this->graphql( compact( 'query', 'variables' ) );
	}

	/**
	 * Asserts that the login mutation succeeded, returning a user with the provider identity linked.
	 *
	 * @param array<string,mixed> $response      The GraphQL response.
	 * @param array<string,mixed> $expected_user The expected user fields, keyed by their GraphQL field name.
	 */
	protected function assert_logged_in( array $response, array $expected_user ): void {
		$user_expectations = [
			$this->expectedObject(
				'auth',
				[
					$this->expectedField( 'isUserSecretRevoked', false ),
					$this->expected_linked_identity(),
					$this->expectedField( 'userSecret', self::NOT_FALSY ),
				]
			),
		];

		foreach ( $expected_user as $field => $value ) {
			$user_expectations[] = $this->expectedField( $field, $value );
		}

		$this->assertArrayNotHasKey( 'errors', $response );
		$this->assertQuerySuccessful(
			$response,
			[
				$this->expectedObject(
					'login',
					[
						$this->expectedField( 'authToken', self::NOT_FALSY ),
						$this->expectedField( 'authTokenExpiration', self::NOT_FALSY ),
						$this->expectedField( 'refreshToken', self::NOT_FALSY ),
						$this->expectedField( 'refreshTokenExpiration', self::NOT_FALSY ),
						$this->expectedObject( 'user', $user_expectations ),
					]
				),
			]
		);
	}

	/**
	 * Asserts that logging in didn't overwrite the existing user's fields with the provider's.
	 *
	 * @param array<string,mixed>  $response        The GraphQL response.
	 * @param array<string,string> $provider_fields The provider's user fields, keyed by their GraphQL field name.
	 */
	private function assert_user_fields_not_overwritten( array $response, array $provider_fields ): void {
		foreach ( $provider_fields as $field => $value ) {
			$this->assertNotEquals( $value, $response['data']['login']['user'][ $field ], sprintf( 'The existing user\'s `%s` was overwritten.', $field ) );
		}
	}

	/**
	 * Returns the expectation that the provider identity is the user's first linked identity.
	 *
	 * @return array<string,mixed>
	 */
	private function expected_linked_identity(): array {
		return $this->expectedNode(
			'linkedIdentities',
			[
				$this->expectedField( 'id', self::IDENTITY_ID ),
				$this->expectedField( 'provider', $this->get_provider_enum() ),
			],
			0
		);
	}

	/**
	 * Runs the linkUserIdentity mutation for the test user.
	 *
	 * @return array<string,mixed>
	 */
	private function link_user_identity(): array {
		$query = '
			mutation LinkUserIdentity( $input: LinkUserIdentityInput! ) {
				linkUserIdentity( input: $input ) {
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

		$variables = [
			'input' => array_merge(
				$this->get_oauth_input(),
				[ 'userId' => $this->test_user ]
			),
		];

		return $this->graphql( compact( 'query', 'variables' ) );
	}
}
