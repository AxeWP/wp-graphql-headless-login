<?php

use Codeception\TestCase\WPTestCase;
use WPGraphQL\Login\Admin\Settings\AccessControlSettings;

class SettingsRestControllerTest extends WPTestCase {
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

		// Create an admin user.
		$this->admin_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );

		// Create a subscriber user.
		$this->subscriber_id = $this->factory()->user->create( [ 'role' => 'subscriber' ] );

		// Set up a REST server instance.
		global $wp_rest_server;

		$wp_rest_server = new \WP_REST_Server();
		$this->server   = $wp_rest_server;
		do_action( 'rest_api_init' );

		$this->endpoint = '/wp-graphql-login/v1/settings';}

	/**
	 * {@inheritDoc}
	 */
	protected function tearDown(): void {
		global $wp_rest_server;
		$wp_rest_server = null;

		// Remove the admin user.
		wp_delete_user( $this->admin_id );
		wp_delete_user( $this->subscriber_id );

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
		wp_set_current_user( $this->admin_id );

		$request = new \WP_REST_Request( 'GET', $this->endpoint );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );

		$values = $response->get_data();

		$this->assertIsArray( $values );

		// Ensure the settings are returned with default values.
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
				'jwt_secret_key'            => '********', // This is sanitized.
			],
		];

		$this->assertSame( $expected, $values );
	}

	/**
	 * Tests get_items with bad permissions.
	 */
	public function testGetItemsBadPermissions(): void {
		// Test as unauthenticated user.
		wp_set_current_user( 0 );

		$request = new \WP_REST_Request( 'GET', $this->endpoint );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 401, $response->get_status() );

		// Test as subscriber.
		wp_set_current_user( $this->subscriber_id );

		$request = new \WP_REST_Request( 'GET', $this->endpoint );

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

		wp_set_current_user( $this->admin_id );

		$request = new \WP_REST_Request( 'POST', $this->endpoint );
		$request->set_param( 'slug', $slug );
		$request->set_param( 'values', $values );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );

		$expected = [
			'wpgraphql_login_access_control' => [
				'shouldBlockUnauthorizedDomains' => true, // This is the only value that should change.
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
				'jwt_secret_key'            => '********', // This is sanitized.
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

		// Test with no slug or values.
		wp_set_current_user( $this->admin_id );

		$request = new \WP_REST_Request( 'POST', $this->endpoint );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );

		// Test with just a slug.
		$request = new \WP_REST_Request( 'POST', $this->endpoint );
		$request->set_param( 'slug', $slug );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );

		// Test with just values.
		$request = new \WP_REST_Request( 'POST', $this->endpoint );
		$request->set_param( 'values', $values );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );

		// Test with invalid slug.
		$request = new \WP_REST_Request( 'POST', $this->endpoint );
		$request->set_param( 'slug', 4 );
		$request->set_param( 'values', $values );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'rest_invalid_param', $response->get_data()['code'] );

		// Test with bad slug.
		$request = new \WP_REST_Request( 'POST', $this->endpoint );
		$request->set_param( 'slug', 'bad-slug' );
		$request->set_param( 'values', $values );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'rest_invalid_param', $response->get_data()['code'] );

		// Test with empty slug.
		$request = new \WP_REST_Request( 'POST', $this->endpoint );
		$request->set_param( 'slug', '' );
		$request->set_param( 'values', $values );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'rest_invalid_param', $response->get_data()['code'] );

		// Test with bad values.
		$request = new \WP_REST_Request( 'POST', $this->endpoint );
		$request->set_param( 'slug', $slug );
		$request->set_param( 'values', 'bad-values' );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'rest_invalid_param', $response->get_data()['code'] );

		// Test with missing required setting.
		$request = new \WP_REST_Request( 'POST', $this->endpoint );
		$request->set_param( 'slug', $slug );
		$request->set_param( 'values', [ 'shouldBlockUnauthorizedDomains' => null ] );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'rest_invalid_param', $response->get_data()['code'] );

		// Test with bad settings.
		$request = new \WP_REST_Request( 'POST', $this->endpoint );
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
		// Test as unauthenticated user.
		wp_set_current_user( 0 );

		$request = new \WP_REST_Request( 'POST', $this->endpoint );
		$request->set_param( 'slug', $slug );
		$request->set_param( 'values', $values );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 401, $response->get_status() );

		// Test as subscriber.
		wp_set_current_user( $this->subscriber_id );

		$request = new \WP_REST_Request( 'POST', $this->endpoint );
		$request->set_param( 'slug', $slug );
		$request->set_param( 'values', $values );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 403, $response->get_status() );
	}

	/**
	 * Tests that Access Control settings are sanitized properly.
	 */
	public function testAccessControlSettingsSanitization(): void {
		// Test sanitization
		$values = [
			'hasSiteAddressInOrigin'         => 'true',
			'shouldBlockUnauthorizedDomains' => '0',
			'customHeaders'                  => [ '*', '<strong>X-Wrapped-In-HTML</strong>' ],
		];

		wp_set_current_user( $this->admin_id );

		$request = new \WP_REST_Request( 'POST', $this->endpoint );
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

		// Test additionalAuthorizedDomains as wildcard string.
		$values['additionalAuthorizedDomains'] = '*';

		$request = new \WP_REST_Request( 'POST', $this->endpoint );
		$request->set_param( 'slug', AccessControlSettings::get_slug() );
		$request->set_param( 'values', $values );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		$this->assertIsArray( $data );
		$this->assertArrayHasKey( AccessControlSettings::get_slug(), $data );

		$actual = $data[ AccessControlSettings::get_slug() ];

		$this->assertEquals( [ '*' ], $actual['additionalAuthorizedDomains'], 'additionalAuthorizedDomains should be an array with a single wildcard.' );

		// Test sanitization of additionalAuthorizedDomains as string.
		$values['additionalAuthorizedDomains'] = 'https://example.com, badurl, https://example.org';

		$request = new \WP_REST_Request( 'POST', $this->endpoint );
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
}
