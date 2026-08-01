<?php
/**
 * Tests the settings REST controller.
 *
 * @package Tests\WPGraphQL\Login\Integration\Settings
 */

namespace Tests\WPGraphQL\Login\Integration\Settings;

use Tests\WPGraphQL\Login\TestCase;
use WPGraphQL\Login\Admin\Settings\AccessControlSettings;
use WPGraphQL\Login\Auth\TokenManager;

/**
 * Tests the Admin\Settings\RestController class.
 */
class SettingsRestControllerTest extends TestCase {
	/**
	 * The Admin ID.
	 */
	private int $admin_id;

	/**
	 * The Subscriber ID.
	 */
	private int $subscriber_id;

	/**
	 * The REST endpoint to use.
	 *
	 * @var string
	 */
	private string $endpoint;

	/**
	 * The REST server instance.
	 *
	 * @var \WP_REST_Server
	 */
	private $server;

	/**
	 * {@inheritDoc}
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->admin_id      = $this->factory()->user->create( [ 'role' => 'administrator' ] );
		$this->subscriber_id = $this->factory()->user->create( [ 'role' => 'subscriber' ] );

		global $wp_rest_server;
		$server_class = 'WP_REST_Server';
		$do_action    = '\\do_action';

		$wp_rest_server = new $server_class();
		$this->server   = $wp_rest_server;
		$do_action( 'rest_api_init' );

		$this->endpoint = '/wp-graphql-login/v1/settings';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function tearDown(): void {
		global $wp_rest_server;
		$wp_delete_user = '\\wp_delete_user';
		$wp_rest_server = null;

		$wp_delete_user( $this->admin_id );
		$wp_delete_user( $this->subscriber_id );

		parent::tearDown();
	}

	/**
	 * Tests the the route is correctly registered.
	 */
	public function testRegisterRoutes(): void {
		$actual = $this->server->get_routes();

		$this->assertArrayHasKey( $this->endpoint, $actual );
	}

	/**
	 * Tests the get_items method.
	 */
	public function testGetItems(): void {
		$this->setCurrentUser( $this->admin_id );

		$request = $this->createRestRequest( 'GET' );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );

		$values = $response->get_data();

		$this->assertIsArray( $values );

		$expected = [
			'wpgraphql_login_access_control' => [
				'shouldBlockUnauthorizedDomains' => false,
				'hasSiteAddressInOrigin'         => false,
				'additionalAuthorizedDomains'    => [],
				'customHeaders'                  => [],
			],
			'wpgraphql_login_cookies'        => [
				'hasAccessControlAllowCredentials' => false,
				'hasLogoutMutation'                => false,
				'sameSiteOption'                   => 'Lax',
				'cookieDomain'                     => '',
			],
			'wpgraphql_login_settings'       => [
				'show_advanced_settings'    => false,
				'delete_data_on_deactivate' => false,
				'jwt_secret_key'            => '********',
			],
		];

		$this->assertSame( $expected, $values );
	}

	/**
	 * Tests get_items with bad permissions.
	 */
	public function testGetItemsBadPermissions(): void {
		$this->setCurrentUser( 0 );

		$request = $this->createRestRequest( 'GET' );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 401, $response->get_status() );

		$this->setCurrentUser( $this->subscriber_id );

		$request = $this->createRestRequest( 'GET' );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 403, $response->get_status() );
	}

	/**
	 * Tests update_item method.
	 */
	public function testUpdateItem(): void {
		$slug   = 'wpgraphql_login_access_control';
		$values = [
			'shouldBlockUnauthorizedDomains' => true,
		];

		$this->setCurrentUser( $this->admin_id );

		$request = $this->createRestRequest( 'POST' );
		$request->set_param( 'slug', $slug );
		$request->set_param( 'values', $values );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );

		$expected = [
			'wpgraphql_login_access_control' => [
				'shouldBlockUnauthorizedDomains' => true,
				'hasSiteAddressInOrigin'         => false,
				'additionalAuthorizedDomains'    => [],
				'customHeaders'                  => [],
			],
			'wpgraphql_login_cookies'        => [
				'hasAccessControlAllowCredentials' => false,
				'hasLogoutMutation'                => false,
				'sameSiteOption'                   => 'Lax',
				'cookieDomain'                     => '',
			],
			'wpgraphql_login_settings'       => [
				'show_advanced_settings'    => false,
				'delete_data_on_deactivate' => false,
				'jwt_secret_key'            => '********',
			],
		];

		$this->assertSame( $expected, $response->get_data() );
	}

	/**
	 * Tests update_item method with bad data.
	 */
	public function testUpdateItemWithBadData(): void {
		$slug   = 'wpgraphql_login_access_control';
		$values = [
			'hasAccessControlAllowCredentials' => true,
		];

		$this->setCurrentUser( $this->admin_id );

		$request = $this->createRestRequest( 'POST' );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );

		$request = $this->createRestRequest( 'POST' );
		$request->set_param( 'slug', $slug );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );

		$request = $this->createRestRequest( 'POST' );
		$request->set_param( 'values', $values );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );

		$request = $this->createRestRequest( 'POST' );
		$request->set_param( 'slug', 4 );
		$request->set_param( 'values', $values );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'rest_invalid_param', $response->get_data()['code'] );

		$request = $this->createRestRequest( 'POST' );
		$request->set_param( 'slug', 'bad-slug' );
		$request->set_param( 'values', $values );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'rest_invalid_param', $response->get_data()['code'] );

		$request = $this->createRestRequest( 'POST' );
		$request->set_param( 'slug', '' );
		$request->set_param( 'values', $values );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'rest_invalid_param', $response->get_data()['code'] );

		$request = $this->createRestRequest( 'POST' );
		$request->set_param( 'slug', $slug );
		$request->set_param( 'values', 'bad-values' );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'rest_invalid_param', $response->get_data()['code'] );

		$request = $this->createRestRequest( 'POST' );
		$request->set_param( 'slug', $slug );
		$request->set_param( 'values', [ 'shouldBlockUnauthorizedDomains' => null ] );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'rest_invalid_param', $response->get_data()['code'] );

		$request = $this->createRestRequest( 'POST' );
		$request->set_param( 'slug', $slug );
		$request->set_param( 'values', [ 'bad-setting' => 'bad-value' ] );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'rest_invalid_param', $response->get_data()['code'] );
	}

	/**
	 * Tests update_item method with bad permissions.
	 */
	public function testUpdateItemWithBadPermissions(): void {
		$slug   = 'wpgraphql_login_access_control';
		$values = [
			'shouldBlockUnauthorizedDomains' => true,
		];

		$this->setCurrentUser( 0 );

		$request = $this->createRestRequest( 'POST' );
		$request->set_param( 'slug', $slug );
		$request->set_param( 'values', $values );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 401, $response->get_status() );

		$this->setCurrentUser( $this->subscriber_id );

		$request = $this->createRestRequest( 'POST' );
		$request->set_param( 'slug', $slug );
		$request->set_param( 'values', $values );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 403, $response->get_status() );
	}

	/**
	 * Tests that settings cannot be updated with masked secret values.
	 */
	public function testSettingsCannotBeUpdatedWithMaskedSecretValues(): void {
		$slug   = 'wpgraphql_login_settings';
		$values = [
			'jwt_secret_key' => '********',
		];

		$this->setCurrentUser( $this->admin_id );
		TokenManager::issue_new_user_secret( $this->admin_id );
		$this->reset_utils_properties();

		$request    = $this->createRestRequest( 'GET' );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$secret_key = TokenManager::get_secret_key();
		$this->assertNotEmpty( $secret_key );
		$this->assertSame( '********', $data[ $slug ]['jwt_secret_key'] );
		$this->assertNotEquals( $data[ $slug ]['jwt_secret_key'], $secret_key );

		$request = $this->createRestRequest( 'POST' );
		$request->set_param( 'slug', $slug );
		$request->set_param( 'values', $values );
		$response = $this->server->dispatch( $request );
		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( $slug, $data );
		$this->assertSame( '********', $data[ $slug ]['jwt_secret_key'] );

		$actual = TokenManager::get_secret_key();
		$this->assertSame( $secret_key, $actual, 'The secret should not have changed.' );
	}

	/**
	 * Tests that Access Control settings are sanitized properly.
	 */
	public function testAccessControlSettingsSanitization(): void {
		$values = [
			'hasSiteAddressInOrigin'         => 'true',
			'shouldBlockUnauthorizedDomains' => '0',
			'customHeaders'                  => [ '*', '<strong>X-Wrapped-In-HTML</strong>' ],
		];

		$this->setCurrentUser( $this->admin_id );

		$request = $this->createRestRequest( 'POST' );
		$request->set_param( 'slug', AccessControlSettings::get_slug() );
		$request->set_param( 'values', $values );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		$this->assertIsArray( $data );
		$this->assertArrayHasKey( AccessControlSettings::get_slug(), $data );

		$actual = $data[ AccessControlSettings::get_slug() ];

		$this->assertTrue( $actual['hasSiteAddressInOrigin'], 'hasSiteAddressInOrigin should be (bool) true.' );
		$this->assertFalse( $actual['shouldBlockUnauthorizedDomains'], 'shouldBlockUnauthorizedDomains should be (bool) false.' );
		$this->assertEquals( [ '*', 'X-Wrapped-In-HTML' ], $actual['customHeaders'], 'customHeaders should be sanitized.' );

		$values['additionalAuthorizedDomains'] = '*';

		$request = $this->createRestRequest( 'POST' );
		$request->set_param( 'slug', AccessControlSettings::get_slug() );
		$request->set_param( 'values', $values );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		$this->assertIsArray( $data );
		$this->assertArrayHasKey( AccessControlSettings::get_slug(), $data );

		$actual = $data[ AccessControlSettings::get_slug() ];

		$this->assertEquals( [ '*' ], $actual['additionalAuthorizedDomains'], 'additionalAuthorizedDomains should be an array with a single wildcard.' );

		$values['additionalAuthorizedDomains'] = 'https://example.com, badurl, https://example.org';

		$request = $this->createRestRequest( 'POST' );
		$request->set_param( 'slug', AccessControlSettings::get_slug() );
		$request->set_param( 'values', $values );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		$this->assertIsArray( $data );
		$this->assertArrayHasKey( AccessControlSettings::get_slug(), $data );

		$actual = $data[ AccessControlSettings::get_slug() ];

		$this->assertEquals( 'https://example.com', $actual['additionalAuthorizedDomains'][0], 'additionalAuthorizedDomains should be an array of sanitized values.' );
		$this->assertStringStartsWith( 'http', $actual['additionalAuthorizedDomains'][1], 'additionalAuthorizedDomains should be an array of sanitized values.' );
		$this->assertEquals( 'https://example.org', $actual['additionalAuthorizedDomains'][2], 'additionalAuthorizedDomains should be an array of sanitized values' );
	}

	/**
	 * Returns a REST request for the settings endpoint.
	 */
	private function createRestRequest( string $method ) {
		$request_class = 'WP_REST_Request';

		return new $request_class( $method, $this->endpoint );
	}

	/**
	 * Wrapper for `wp_set_current_user()`.
	 */
	private function setCurrentUser( int $user_id ): void {
		$wp_set_current_user = '\\wp_set_current_user';
		$wp_set_current_user( $user_id );
	}
}
