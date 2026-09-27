<?php
/**
 * Tests the Site Token provider login flow.
 *
 * @package Tests\WPGraphQL\Login\Integration\Functional
 */

namespace Tests\WPGraphQL\Login\Integration\Functional;

use Tests\WPGraphQL\Login\TestCase;
use WPGraphQL\Login\Admin\Settings\AccessControlSettings;
use WPGraphQL\Login\Admin\Settings\ProviderSettings;

/**
 * Tests logging in with the Site Token provider.
 */
class SiteTokenAuthenticationTest extends TestCase {
	/**
	 * {@inheritDoc}
	 */
	public function setUp(): void {
		parent::setUp();

		$this->set_client_config(
			'siteToken',
			[
				'name'          => 'Site Token',
				'slug'          => 'siteToken',
				'order'         => 0,
				'isEnabled'     => true,
				'clientOptions' => [
					'headerKey' => 'X-My-Secret-Auth-Token',
					'secretKey' => 'some_secret',
				],
				'loginOptions'  => [
					'useAuthenticationCookie' => true,
					'metaKey'                 => 'email',
				],
			]
		);

		update_option( 'graphql_general_settings', [ 'debug_mode_enabled' => 'on' ] );
		$this->reset_utils_properties();
		$this->clearSchema();
	}

	/**
	 * {@inheritDoc}
	 */
	public function tearDown(): void {
		unset( $_SERVER['HTTP_ORIGIN'], $_SERVER['HTTP_X_MY_SECRET_AUTH_TOKEN'] );
		delete_option( ProviderSettings::$settings_prefix . 'siteToken' );
		delete_option( AccessControlSettings::get_slug() );
		$this->reset_utils_properties();
		$this->clearSchema();

		parent::tearDown();
	}

	public function test_login_with_site_token_respects_access_control_and_sets_auth_cookie(): void {
		$this->factory()->user->create(
			[
				'role'       => 'administrator',
				'user_login' => 'testuser',
				'user_pass'  => 'testpass',
				'user_email' => 'some_email@test.com',
			]
		);

		$query = '
			mutation LoginWithSiteToken( $identity: String! ) {
				login( input: { identity: $identity, provider: SITETOKEN } ) {
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
						email
					}
				}
			}
		';

		$variables = [
			'identity' => 'some_email@test.com',
		];

		$_SERVER['HTTP_X_MY_SECRET_AUTH_TOKEN'] = 'some_secret';

		$blocked = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayHasKey( 'errors', $blocked );
		$debug_message = $blocked['errors'][0]['extensions']['debugMessage'] ?? $blocked['errors'][0]['debugMessage'] ?? '';
		$this->assertSame( 'Provider siteToken is not enabled.', $debug_message );

		update_option(
			AccessControlSettings::get_slug(),
			[
				'shouldBlockUnauthorizedDomains' => true,
				'hasSiteAddressInOrigin'         => true,
			]
		);
		$this->reset_utils_properties();
		$this->reset_provider_registry();

		$_SERVER['HTTP_ORIGIN'] = site_url();

		$allowed = $this->capture_auth_cookie_events(
			function () use ( $query, $variables ) {
				return $this->graphql( compact( 'query', 'variables' ) );
			}
		);

		$response = $allowed['response'];
		$events   = $allowed['events'];

		$this->assertArrayNotHasKey( 'errors', $response );
		$this->assertSame( [], $this->get_debug_messages( $response ) );
		$this->assertNotEmpty( $response['data']['login']['authToken'] );
		$this->assertNotEmpty( $response['data']['login']['authTokenExpiration'] );
		$this->assertNotEmpty( $response['data']['login']['refreshToken'] );
		$this->assertNotEmpty( $response['data']['login']['refreshTokenExpiration'] );
		$this->assertSame( 'testuser', $response['data']['login']['user']['username'] );
		$this->assertSame( 'some_email@test.com', $response['data']['login']['user']['email'] );
		$this->assertNotEmpty( $events['set_logged_in_cookie'] );
	}
}
