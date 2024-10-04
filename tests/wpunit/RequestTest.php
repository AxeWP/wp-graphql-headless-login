<?php

use WPGraphQL\Login\Admin\Settings\AccessControlSettings;
use WPGraphQL\Login\Auth\Request;
use WPGraphQL\Login\Auth\TokenManager;

/**
 * Test Auth\Request class
 *
 * @coversDefaultClass \WPGraphQL\Login\Auth\Request
 */
class RequestTest extends \lucatume\WPBrowser\TestCase\WPTestCase {
	/**
	 * @var \WpunitTester
	 */
	public $tester;

	public $default_options = [
		'shouldBlockUnauthorizedDomains'   => false,
		'hasAccessControlAllowCredentials' => false,
		'hasSiteAddressInOrigin'           => false,
		'additionalAuthorizedDomains'      => [],
	];

	/**
	 * {@inheritDoc}
	 */
	public function setUp(): void {
		parent::setUp();

		update_option( AccessControlSettings::get_slug(), $this->default_options );

		$this->tester->reset_utils_properties();
	}

	/**
	 * {@inheritDoc}
	 */
	public function tearDown(): void {
		delete_option( AccessControlSettings::get_slug() );
		$this->tester->reset_utils_properties();

		parent::tearDown();
	}

	public function testAuthenticateTokenOnRequest(): void {
		$debug_log = new \WPGraphQL\Utils\DebugLog();

		Request::authenticate_token_on_request();

		$actual = $debug_log->get_logs();

		$this->assertEmpty( $actual );

		// Test with invalid token.
		$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer not-a-valid-auth';

		Request::authenticate_token_on_request();

		$actual = $debug_log->get_logs();

		$this->assertNotEmpty( $actual, 'Debug log should not be empty' );
		$this->assertCount( 1, $actual, 'Debug log should have 1 entry' );

		$expected = 'invalid-secret-key | Wrong number of segments';
		$this->assertEquals( $expected, $actual[0]['message'], 'Debug log should contain expected message' );

		// cleanup
		unset( $_SERVER['HTTP_AUTHORIZATION'] );
	}

	public function testAuthenticateOriginOnRequestWithUnauthorizedDomain() {
		// Test with no origin set doesnt throw an error.
		Request::authenticate_origin_on_request();

		// Test with any origin set doesnt throw an error.
		$_SERVER['HTTP_ORIGIN'] = 'https://example.com';

		Request::authenticate_origin_on_request();

		// Test with shouldBlockUnauthorizedDomains set to true.

		update_option( AccessControlSettings::get_slug(), array_merge( $this->default_options, [ 'shouldBlockUnauthorizedDomains' => true ] ) );
		$this->tester->reset_utils_properties();

		// If the origin is the same as the host this should be fine.
		$_SERVER['HTTP_ORIGIN'] = 'http://' . $_SERVER['HTTP_HOST'];

		Request::authenticate_origin_on_request();

		// If the origin isn't set, this should throw an error.

		unset( $_SERVER['HTTP_ORIGIN'] );
		unset( $_SERVER['HTTP_REFERER'] );

		$this->expectException( \GraphQL\Error\UserError::class );
		$this->expectExceptionMessage( 'Unauthorized request origin.' );

		Request::authenticate_origin_on_request();
	}

	public function testAuthenticateOriginOnRequestWithSiteAddress() {
		update_option( 'home', 'https://example.com' );
		update_option(
			AccessControlSettings::get_slug(),
			array_merge(
				$this->default_options,
				[
					'shouldBlockUnauthorizedDomains' => true,
					'hasSiteAddressInOrigin'         => true,
				]
			)
		);
		$this->tester->reset_utils_properties();

		// Test with HTTP_ORIGIN set to the home.
		$_SERVER['HTTP_ORIGIN'] = 'https://example.com';

		// This will pass with hasSiteAddressInOrigin set to true.
		update_option(
			AccessControlSettings::get_slug(),
			array_merge(
				$this->default_options,
				[
					'shouldBlockUnauthorizedDomains' => true,
					'hasSiteAddressInOrigin'         => true,
				]
			)
		);
		$this->tester->reset_utils_properties();

		Request::authenticate_origin_on_request();

		// This will fail with hasSiteAddressInOrigin set to false.
		update_option(
			AccessControlSettings::get_slug(),
			array_merge(
				$this->default_options,
				[
					'shouldBlockUnauthorizedDomains' => true,
					'hasSiteAddressInOrigin'         => false,
				]
			)
		);
		$this->tester->reset_utils_properties();

		$this->expectException( \GraphQL\Error\UserError::class );
		$this->expectExceptionMessage( 'Unauthorized request origin.' );

		Request::authenticate_origin_on_request();

		// cleanup
		unset( $_SERVER['HTTP_ORIGIN'] );
		update_option( 'home', 'http://' . $_SERVER['HTTP_HOST'] );
	}

	public function testAuthenticateOriginOnRequestWithAdditionalDomains() {
		update_option(
			AccessControlSettings::get_slug(),
			array_merge(
				$this->default_options,
				[
					'shouldBlockUnauthorizedDomains' => true,
					'additionalAuthorizedDomains'    => [
						'https://example.com',
					],
				]
			)
		);
		$this->tester->reset_utils_properties();

		// Test with HTTP_ORIGIN set to the additional domain.
		$_SERVER['HTTP_ORIGIN'] = 'https://example.com';

		Request::authenticate_origin_on_request();

		// Test with a protocol mismatch will pass.
		$_SERVER['HTTP_ORIGIN'] = 'http://example.com';

		Request::authenticate_origin_on_request();

		// Test with a subdomain will pass.
		$_SERVER['HTTP_ORIGIN'] = 'https://subdomain.example.com';

		// This will fail with additionalAuthorizedDomains set to false.
		update_option(
			AccessControlSettings::get_slug(),
			array_merge(
				$this->default_options,
				[
					'shouldBlockUnauthorizedDomains' => true,
					'additionalAuthorizedDomains'    => [],
				]
			)
		);
		$this->tester->reset_utils_properties();

		$this->expectException( \GraphQL\Error\UserError::class );
		$this->expectExceptionMessage( 'Unauthorized request origin.' );

		Request::authenticate_origin_on_request();

		// cleanup
		unset( $_SERVER['HTTP_ORIGIN'] );
	}

	public function testResponseHeadersToSend(): void {
		$default_client_config = [
			'name'          => 'Site Token',
			'slug'          => 'siteToken',
			'order'         => 0,
			'isEnabled'     => false,
			'clientOptions' => [
				'headerKey' => 'X-My-Secret-Auth-Token',
				'secretKey' => 'some_secret',
			],
			'loginOptions'  => [
				'useAuthenticationCookie' => true,
				'metaKey'                 => 'email',
			],
		];
		$this->tester->set_client_config( 'siteToken', $default_client_config );

		$default_headers = [
			'Access-Control-Allow-Origin'   => '*',
			'Access-Control-Allow-Headers'  => implode(
				', ',
				[
					'Authorization',
					'Content-Type',
					'X-Custom-Header',
				]
			),
			'Access-Control-Expose-Headers' => 'X-Custom-Header',
			'Access-Control-Max-Age'        => 600,
			// cache the result of preflight requests (600 is the upper limit for Chromium).
			'Content-Type'                  => 'application/json ; charset=' . get_option( 'blog_charset' ),
			'X-Robots-Tag'                  => 'noindex',
			'X-Content-Type-Options'        => 'nosniff',
			'X-GraphQL-URL'                 => graphql_get_endpoint_url(),
			'Vary'                          => 'X-Custom-Header',
		];

		$actual = Request::response_headers_to_send( $default_headers );

		codecept_debug( $actual );

		// Check Access-Control-Allow-Origin.
		$this->assertArrayHasKey( 'Access-Control-Allow-Origin', $actual );
		$this->assertStringContainsString( '*', $actual['Access-Control-Allow-Origin'] );

		// Check Access-Control-Allow-Credentials.
		$this->assertArrayNotHasKey( 'Access-Control-Allow-Credentials', $actual );

		// Check Access-Control-Expose-Headers.
		$this->assertArrayHasKey( 'Access-Control-Expose-Headers', $actual );
		$this->assertStringContainsString( 'X-Custom-Header', $actual['Access-Control-Expose-Headers'] );
		$this->assertStringNotContainsString( 'X-WPGraphQL-Login-Refresh-Token', $actual['Access-Control-Expose-Headers'] );

		// Check Access-Control-Allow-Headers.
		$this->assertArrayHasKey( 'Access-Control-Allow-Headers', $actual );
		$this->assertStringContainsString( 'Authorization', $actual['Access-Control-Allow-Headers'] );
		$this->assertStringContainsString( 'Content-Type', $actual['Access-Control-Allow-Headers'] );
		$this->assertStringContainsString( 'X-Custom-Header', $actual['Access-Control-Allow-Headers'] );
		$this->assertStringContainsString( 'X-WPGraphQL-Login-Refresh-Token', $actual['Access-Control-Allow-Headers'] );
		$this->assertStringNotContainsString( 'X-My-Secret-Auth-Token', $actual['Access-Control-Allow-Headers'] );

		// Check Vary.
		$this->assertArrayHasKey( 'Vary', $actual );
		$this->assertStringContainsString( 'X-Custom-Header', $actual['Vary'] );
		$this->assertStringContainsString( 'Origin', $actual['Vary'] );

		// Test with hasAccessControlAllowCredentials.
		update_option(
			AccessControlSettings::get_slug(),
			array_merge(
				$this->default_options,
				[
					'hasAccessControlAllowCredentials' => true,
				]
			)
		);

		$this->tester->reset_utils_properties();

		$actual = Request::response_headers_to_send( $default_headers );

		codecept_debug( $actual );

		// Check Access-Control-Allow-Origin.
		$this->assertArrayHasKey( 'Access-Control-Allow-Origin', $actual );
		$this->assertStringContainsString( '*', $actual['Access-Control-Allow-Origin'] );

		// Check Access-Control-Allow-Credentials.
		$this->assertArrayNotHasKey( 'Access-Control-Allow-Credentials', $actual );

		// Test with hasAccessControlAllowCredentials and shouldBlockUnauthorizedDomains.
		update_option(
			AccessControlSettings::get_slug(),
			array_merge(
				$this->default_options,
				[
					'shouldBlockUnauthorizedDomains'   => true,
					'hasAccessControlAllowCredentials' => true,
				]
			)
		);

		$this->tester->reset_utils_properties();

		$actual = Request::response_headers_to_send( $default_headers );

		codecept_debug( $actual );

		// Check Access-Control-Allow-Origin.
		$this->assertArrayHasKey( 'Access-Control-Allow-Origin', $actual );
		$this->assertStringContainsString( '', $actual['Access-Control-Allow-Origin'] );

		// Check Access-Control-Allow-Credentials.
		$this->assertArrayHasKey( 'Access-Control-Allow-Credentials', $actual );

		// Test with custom headers and explicit origin.
		$default_client_config['isEnabled'] = true;
		$this->tester->set_client_config( 'siteToken', $default_client_config );
		$_SERVER['HTTP_ORIGIN'] = site_url();

		update_option(
			AccessControlSettings::get_slug(),
			array_merge(
				$this->default_options,
				[
					'customHeaders' => [
						'X-Custom-Header',
						'X-Custom-Header-2',
					],
				]
			)
		);
		$this->tester->reset_utils_properties();

		$actual = Request::response_headers_to_send( $default_headers );

		codecept_debug( $actual );

		// Check Access-Control-Allow-Headers.
		$this->assertArrayHasKey( 'Access-Control-Allow-Headers', $actual );
		$this->assertStringContainsString( 'Authorization', $actual['Access-Control-Allow-Headers'] );
		$this->assertStringContainsString( 'Content-Type', $actual['Access-Control-Allow-Headers'] );
		$this->assertStringContainsString( 'X-Custom-Header', $actual['Access-Control-Allow-Headers'] );
		$this->assertStringContainsString( 'X-Custom-Header-2', $actual['Access-Control-Allow-Headers'] );
		$this->assertStringContainsString( 'X-WPGraphQL-Login-Token', $actual['Access-Control-Allow-Headers'] );
		$this->assertStringContainsString( 'X-WPGraphQL-Login-Refresh-Token', $actual['Access-Control-Allow-Headers'] );
		$this->assertStringNotContainsString( 'X-My-Secret-Auth-Token', $actual['Access-Control-Allow-Headers'] );

		// Check Vary.
		$this->assertArrayHasKey( 'Vary', $actual );
		$this->assertStringContainsString( 'X-Custom-Header', $actual['Vary'] );
		$this->assertStringNotContainsString( 'Origin', $actual['Vary'] );

		// Test with authenticated user and shouldBlockUnauthorizedDomains
		$user_id = $this->factory()->user->create(
			[
				'role' => 'administrator',
			]
		);

		update_option(
			AccessControlSettings::get_slug(),
			array_merge(
				$this->default_options,
				[
					'shouldBlockUnauthorizedDomains' => true,
				]
			)
		);

		$tokens = $this->tester->generate_user_tokens( $user_id );

		$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $tokens['auth_token'];

		$actual = Request::response_headers_to_send( $default_headers );

		codecept_debug( $actual );

		// Check SiteToken header.
		$this->assertStringContainsString( 'X-My-Secret-Auth-Token', $actual['Access-Control-Allow-Headers'] );

		// Check exposed headers.
		$this->assertArrayHasKey( 'Access-Control-Expose-Headers', $actual );
		$this->assertStringContainsString( 'X-Custom-Header', $actual['Access-Control-Expose-Headers'] );
		$this->assertStringContainsString( 'X-WPGraphQL-Login-Refresh-Token', $actual['Access-Control-Expose-Headers'] );

		// Check Token headers.
		$this->assertArrayHasKey( 'X-WPGraphQL-Login-Token', $actual );

		$token = TokenManager::validate_token( $actual['X-WPGraphQL-Login-Token'], false );
		$this->assertEquals( $user_id, $token->data->user->id );

		$this->assertArrayHasKey( 'X-WPGraphQL-Login-Refresh-Token', $actual );
		$refresh_token = TokenManager::validate_token( $actual['X-WPGraphQL-Login-Refresh-Token'], true );
		$this->assertEquals( $user_id, $refresh_token->data->user->id );
		$this->assertNotEmpty( $refresh_token->data->user->user_secret );
	}

	public function testGetAcaoHeader(): void {
		$default_headers = [
			'Access-Control-Allow-Origin'   => '*',
			'Access-Control-Allow-Headers'  => implode(
				', ',
				[
					'Authorization',
					'Content-Type',
					'X-Custom-Header',
				]
			),
			'Access-Control-Expose-Headers' => 'X-Custom-Header',
			'Access-Control-Max-Age'        => 600,
			// cache the result of preflight requests (600 is the upper limit for Chromium).
			'Content-Type'                  => 'application/json ; charset=' . get_option( 'blog_charset' ),
			'X-Robots-Tag'                  => 'noindex',
			'X-Content-Type-Options'        => 'nosniff',
			'X-GraphQL-URL'                 => graphql_get_endpoint_url(),
		];

		// Test with custom headers.
		update_option(
			AccessControlSettings::get_slug(),
			array_merge(
				$this->default_options,
				[
					'shouldBlockUnauthorizedDomains' => true,
				]
			)
		);
		$this->tester->reset_utils_properties();

		// Test with shouldBlockUnauthorizedDomains set to true.
		$actual = Request::response_headers_to_send( $default_headers );

		codecept_debug( $actual );

		$this->assertArrayHasKey( 'Access-Control-Allow-Origin', $actual );
		$this->assertStringContainsString( site_url(), $actual['Access-Control-Allow-Origin'] );

		// Test external origin doesnt change ACAO header.
		$_SERVER['HTTP_ORIGIN'] = 'https://example.com';

		$actual = Request::response_headers_to_send( $default_headers );

		codecept_debug( $actual );

		$this->assertArrayHasKey( 'Access-Control-Allow-Origin', $actual );
		$this->assertStringContainsString( site_url(), $actual['Access-Control-Allow-Origin'] );

		// Test with hasSiteAddressInOrigin set to true.
		update_option( 'home', 'https://example.com' );
		update_option(
			AccessControlSettings::get_slug(),
			array_merge(
				$this->default_options,
				[
					'shouldBlockUnauthorizedDomains' => true,
					'hasSiteAddressInOrigin'         => true,
				]
			)
		);
		$this->tester->reset_utils_properties();

		$actual = Request::response_headers_to_send( $default_headers );

		codecept_debug( $actual );

		$this->assertArrayHasKey( 'Access-Control-Allow-Origin', $actual );
		$this->assertStringContainsString( home_url(), $actual['Access-Control-Allow-Origin'] );

		// Test with additionalAuthorizedDomains
		update_option(
			AccessControlSettings::get_slug(),
			array_merge(
				$this->default_options,
				[
					'shouldBlockUnauthorizedDomains' => true,
					'hasSiteAddressInOrigin'         => true,
					'additionalAuthorizedDomains'    => [
						'https://example2.com',
					],
				]
			)
		);
		$this->tester->reset_utils_properties();

		$_SERVER['HTTP_ORIGIN'] = 'https://example2.com';

		$actual = Request::response_headers_to_send( $default_headers );

		codecept_debug( $actual );

		$this->assertArrayHasKey( 'Access-Control-Allow-Origin', $actual );
		$this->assertStringContainsString( 'https://example2.com', $actual['Access-Control-Allow-Origin'] );
	}
}
