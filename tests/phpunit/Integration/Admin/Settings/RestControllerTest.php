<?php
/**
 * Tests the settings REST controller.
 *
 * @package WPGraphQL\Login\Tests\Integration\Admin\Settings
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Tests\Integration\Admin\Settings;

use PHPUnit\Framework\Attributes\CoversClass;
use WPGraphQL\Login\Admin\Settings\AbstractSettings;
use WPGraphQL\Login\Admin\Settings\AccessControlSettings;
use WPGraphQL\Login\Admin\Settings\CookieSettings;
use WPGraphQL\Login\Admin\Settings\PluginSettings;
use WPGraphQL\Login\Admin\Settings\ProviderSettings;
use WPGraphQL\Login\Admin\Settings\RestController;
use WPGraphQL\Login\Auth\TokenManager;
use WPGraphQL\Login\Tests\TestCase;
use WP_REST_Request;
use WP_REST_Server;

/**
 * Tests the Admin\Settings\RestController class.
 */
#[CoversClass( RestController::class )]
#[CoversClass( AbstractSettings::class )]
#[CoversClass( AccessControlSettings::class )]
#[CoversClass( CookieSettings::class )]
#[CoversClass( PluginSettings::class )]
#[CoversClass( ProviderSettings::class )]
class RestControllerTest extends TestCase {
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
	 */
	private string $endpoint;

	/**
	 * The REST server instance.
	 */
	private WP_REST_Server $server;

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

		$wp_rest_server = new WP_REST_Server();
		$this->server   = $wp_rest_server;
		do_action( 'rest_api_init' );

		$this->endpoint = '/wp-graphql-login/v1/settings';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function tearDown(): void {
		global $wp_rest_server;
		$wp_rest_server = null;

		parent::tearDown();
	}

	/**
	 * Tests that the settings route is registered on `rest_api_init`.
	 */
	public function test_settings_route_is_registered(): void {
		$actual = $this->server->get_routes();

		$this->assertArrayHasKey( $this->endpoint, $actual );
	}

	/**
	 * Tests that get_items() returns every setting group with its default values.
	 */
	public function test_get_items_returns_default_settings(): void {
		wp_set_current_user( $this->admin_id );

		$request = new WP_REST_Request( 'GET', $this->endpoint );

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
	 * Tests that get_items() rejects logged-out users and users without `manage_options`.
	 */
	public function test_get_items_rejects_unauthorized_users(): void {
		// Test as unauthenticated user.
		wp_set_current_user( 0 );

		$request = new WP_REST_Request( 'GET', $this->endpoint );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 401, $response->get_status() );

		// Test as subscriber.
		wp_set_current_user( $this->subscriber_id );

		$request = new WP_REST_Request( 'GET', $this->endpoint );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 403, $response->get_status() );
	}

	/**
	 * Tests that update_item() updates only the given values and returns all the settings.
	 */
	public function test_update_item_updates_only_given_values(): void {
		$slug   = 'wpgraphql_login_access_control';
		$values = [
			'shouldBlockUnauthorizedDomains' => true,
		];

		wp_set_current_user( $this->admin_id );

		$request = new WP_REST_Request( 'POST', $this->endpoint );
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
	 * Tests that update_item() rejects missing or invalid slugs and values.
	 */
	public function test_update_item_rejects_invalid_params(): void {
		$slug   = 'wpgraphql_login_access_control';
		$values = [
			'hasAccessControlAllowCredentials' => true,
		];

		// Test with no slug or values.
		wp_set_current_user( $this->admin_id );

		$request = new WP_REST_Request( 'POST', $this->endpoint );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );

		// Test with just a slug.
		$request = new WP_REST_Request( 'POST', $this->endpoint );
		$request->set_param( 'slug', $slug );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );

		// Test with just values.
		$request = new WP_REST_Request( 'POST', $this->endpoint );
		$request->set_param( 'values', $values );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );

		// Test with invalid slug.
		$request = new WP_REST_Request( 'POST', $this->endpoint );
		$request->set_param( 'slug', 4 );
		$request->set_param( 'values', $values );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'rest_invalid_param', $response->get_data()['code'] );

		// Test with bad slug.
		$request = new WP_REST_Request( 'POST', $this->endpoint );
		$request->set_param( 'slug', 'bad-slug' );
		$request->set_param( 'values', $values );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'rest_invalid_param', $response->get_data()['code'] );

		// Test with empty slug.
		$request = new WP_REST_Request( 'POST', $this->endpoint );
		$request->set_param( 'slug', '' );
		$request->set_param( 'values', $values );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'rest_invalid_param', $response->get_data()['code'] );

		// Test with bad values.
		$request = new WP_REST_Request( 'POST', $this->endpoint );
		$request->set_param( 'slug', $slug );
		$request->set_param( 'values', 'bad-values' );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'rest_invalid_param', $response->get_data()['code'] );

		// Test with missing required setting.
		$request = new WP_REST_Request( 'POST', $this->endpoint );
		$request->set_param( 'slug', $slug );
		$request->set_param( 'values', [ 'shouldBlockUnauthorizedDomains' => null ] );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'rest_invalid_param', $response->get_data()['code'] );

		// Test with bad settings.
		$request = new WP_REST_Request( 'POST', $this->endpoint );
		$request->set_param( 'slug', $slug );
		$request->set_param( 'values', [ 'bad-setting' => 'bad-value' ] );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'rest_invalid_param', $response->get_data()['code'] );
	}

	/**
	 * Tests that update_item() rejects logged-out users and users without `manage_options`.
	 */
	public function test_update_item_rejects_unauthorized_users(): void {
		$slug   = 'wpgraphql_login_access_control';
		$values = [
			'shouldBlockUnauthorizedDomains' => true,
		];

		// Test as unauthenticated user.
		wp_set_current_user( 0 );

		$request = new WP_REST_Request( 'POST', $this->endpoint );
		$request->set_param( 'slug', $slug );
		$request->set_param( 'values', $values );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 401, $response->get_status() );

		// Test as subscriber.
		wp_set_current_user( $this->subscriber_id );

		$request = new WP_REST_Request( 'POST', $this->endpoint );
		$request->set_param( 'slug', $slug );
		$request->set_param( 'values', $values );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 403, $response->get_status() );
	}

	/**
	 * Tests that settings cannot be updated with masked secret values.
	 */
	public function test_settings_cannot_be_updated_with_masked_secret_values(): void {
		$slug   = 'wpgraphql_login_settings';
		$values = [
			'jwt_secret_key' => '********',
		];

		// Set a real secret first.
		wp_set_current_user( $this->admin_id );
		TokenManager::issue_new_user_secret( $this->admin_id );
		$this->reset_utils_properties();

		// First confirm that the real secret is set.
		$request    = new WP_REST_Request( 'GET', $this->endpoint );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$secret_key = TokenManager::get_secret_key();
		$this->assertNotEmpty( $secret_key );
		$this->assertSame( '********', $data[ $slug ]['jwt_secret_key'] );
		$this->assertNotEquals( $data[ $slug ]['jwt_secret_key'], $secret_key );

		// Now try to update the setting with the masked value.
		$request = new WP_REST_Request( 'POST', $this->endpoint );
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
	 * Tests that update_item() sanitizes the Access Control settings.
	 */
	public function test_update_item_sanitizes_access_control_settings(): void {
		$values = [
			'hasSiteAddressInOrigin'         => 'true',
			'shouldBlockUnauthorizedDomains' => '0',
			'customHeaders'                  => [ '*', '<strong>X-Wrapped-In-HTML</strong>' ],
		];

		wp_set_current_user( $this->admin_id );

		$request = new WP_REST_Request( 'POST', $this->endpoint );
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

		$request = new WP_REST_Request( 'POST', $this->endpoint );
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

		$request = new WP_REST_Request( 'POST', $this->endpoint );
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
