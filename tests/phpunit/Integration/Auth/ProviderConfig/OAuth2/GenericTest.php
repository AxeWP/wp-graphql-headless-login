<?php
/**
 * Tests the login and linkUserIdentity mutations for the Generic OAuth2 provider.
 *
 * @package WPGraphQL\Login\Tests\Integration\Auth\ProviderConfig\OAuth2
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Tests\Integration\Auth\ProviderConfig\OAuth2;

use PHPUnit\Framework\Attributes\CoversClass;
use WPGraphQL\Login\Auth\Auth;
use WPGraphQL\Login\Auth\Client;
use WPGraphQL\Login\Auth\ProviderConfig\OAuth2\Generic;
use WPGraphQL\Login\Auth\ProviderConfig\OAuth2\OAuth2Config;
use WPGraphQL\Login\Auth\User;
use WPGraphQL\Login\GraphQL\Type\Mutation\LinkUserIdentity;
use WPGraphQL\Login\GraphQL\Type\Mutation\Login;
use WPGraphQL\Login\Tests\Fixtures\FooGenericProviderConfig;

/**
 * Tests the Generic OAuth2 provider mutations.
 */
#[CoversClass( Generic::class )]
#[CoversClass( OAuth2Config::class )]
#[CoversClass( Login::class )]
#[CoversClass( LinkUserIdentity::class )]
#[CoversClass( Auth::class )]
#[CoversClass( Client::class )]
#[CoversClass( User::class )]
class GenericTest extends OAuth2ConfigTestCase {
	/**
	 * {@inheritDoc}
	 */
	protected function tearDown(): void {
		unset( $_SESSION['oauth2state'] ); // phpcs:ignore WordPressVIPMinimum.Variables.RestrictedVariables.session___SESSION -- Clears the state set by test_login_with_oauth2_state().

		parent::tearDown();
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_provider_slug(): string {
		return 'oauth2-generic';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_provider_enum(): string {
		return 'OAUTH2_GENERIC';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_client_options(): array {
		return [
			'urlAuthorize'            => 'http://example.com/authorize',
			'urlAccessToken'          => 'http://example.com/token',
			'urlResourceOwnerDetails' => 'http://example.com/user',
			'scope'                   => [
				'email',
				'public_profile',
			],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	protected function create_provider_config(): OAuth2Config {
		return new FooGenericProviderConfig();
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_provider_user_fields(): array {
		return [
			'email'     => 'mock_email@email.com',
			'firstName' => 'mock_first_name',
			'lastName'  => 'mock_last_name',
			'username'  => 'mock_username',
		];
	}

	/**
	 * Tests that logging in without the `oauthResponse` input returns an error.
	 */
	public function test_login_without_oauth_response(): void {
		$actual = $this->login( [ 'provider' => 'OAUTH2_GENERIC' ] );

		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertSame( 'The oauth2-generic provider requires the use of the `oauthResponse` input arg.', $actual['errors'][0]['message'] );
	}

	/**
	 * Tests that a `state` that doesn't match the one stored in the session is rejected, and a matching one logs in.
	 */
	public function test_login_with_oauth2_state(): void {
		User::link_user_identity( $this->test_user, 'oauth2-generic', self::IDENTITY_ID );

		$_SESSION['oauth2state'] = 'mock_state'; // phpcs:ignore WordPressVIPMinimum.Variables.RestrictedVariables.session___SESSION -- Simulates the state stored when the authorization URL was generated.

		// Test with a bad state.
		$input                           = $this->get_oauth_input();
		$input['oauthResponse']['state'] = 'bad_state';

		$actual = $this->login( $input );

		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertSame( 'The state returned from the oauth2-generic response does not match.', $actual['errors'][0]['message'] );

		// Test with a good state.
		$input['oauthResponse']['state'] = 'mock_state';

		$this->assert_logged_in( $this->login( $input ), [ 'databaseId' => $this->test_user ] );
	}

	/**
	 * Tests that `useAuthenticationCookie` sets the auth cookies for the logged-in user.
	 */
	public function test_login_with_auth_cookie(): void {
		User::link_user_identity( $this->test_user, 'oauth2-generic', self::IDENTITY_ID );
		$this->set_provider_settings( [ 'useAuthenticationCookie' => true ] );

		$login = $this->capture_auth_cookie_events( fn (): array => $this->login() );

		$this->assert_logged_in( $login['response'], [ 'databaseId' => $this->test_user ] );
		$this->assertCount( 1, $login['events']['set_auth_cookie'] );
		$this->assertSame( $this->test_user, $login['events']['set_auth_cookie'][0][3] );
		$this->assertCount( 1, $login['events']['set_logged_in_cookie'] );
		$this->assertSame( $this->test_user, $login['events']['set_logged_in_cookie'][0][3] );
	}

	/**
	 * Tests that a new user gets a unique username when the provider's is already taken.
	 */
	public function test_login_with_create_user_increments_taken_username(): void {
		$this->set_provider_settings( [ 'createUserIfNoneExists' => true ] );

		$this->factory()->user->create( [ 'user_login' => 'mock_username' ] );
		$this->factory()->user->create( [ 'user_login' => 'mock_username2' ] );

		$actual = $this->login();

		$this->assert_logged_in( $actual, [ 'username' => 'mock_username3' ] );
	}

	/**
	 * Tests that no user is matched without the provider's identity.
	 */
	public function test_get_user_from_data_requires_identity(): void {
		User::link_user_identity( $this->test_user, 'oauth2-generic', self::IDENTITY_ID );

		$config = new FooGenericProviderConfig();

		$this->assertFalse( $config->get_user_from_data( [ 'user_email' => 'mock_email@email.com' ] ) );
		$this->assertSame( $this->test_user, $config->get_user_from_data( [ 'subject_identity' => self::IDENTITY_ID ] )->ID );
	}

	/**
	 * Tests that a `state` added to the client options is used in the authorization URL.
	 */
	public function test_authorization_url_uses_state_from_client_options(): void {
		add_filter(
			'graphql_login_client_options',
			static fn ( array $options ): array => array_merge( $options, [ 'state' => 'my_state' ] )
		);

		$authorization_url = ( new Generic() )->get_authorization_url();

		parse_str( (string) wp_parse_url( $authorization_url, PHP_URL_QUERY ), $query_args );

		$this->assertStringStartsWith( 'http://example.com/authorize', $authorization_url );
		$this->assertSame( 'my_state', $query_args['state'] );
		$this->assertSame( 'mock_redirect_uri', $query_args['redirect_uri'] );
	}

	/**
	 * Tests that the provider class must be an OAuth2 provider.
	 */
	public function test_provider_class_must_extend_abstract_provider(): void {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'The provider class must extend AbstractProvider. stdClass does not' );

		new class() extends Generic {
			/**
			 * Builds the config with a provider class that isn't an OAuth2 provider.
			 */
			public function __construct() {
				OAuth2Config::__construct( \stdClass::class );
			}
		};
	}
}
