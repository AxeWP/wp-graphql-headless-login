<?php
/**
 * Tests the Auth\Request class.
 *
 * @package WPGraphQL\Login\Tests\Integration\Auth
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Tests\Integration\Auth;

use Closure;
use GraphQL\Error\UserError;
use PHPUnit\Framework\Attributes\CoversClass;
use WPGraphQL\Login\Admin\Settings\AccessControlSettings;
use WPGraphQL\Login\Admin\Settings\CookieSettings;
use WPGraphQL\Login\Auth\Request;
use WPGraphQL\Login\Auth\TokenManager;
use WPGraphQL\Login\Tests\TestCase;
use WPGraphQL\Utils\DebugLog;

/**
 * Tests authenticating the request token and origin, and the response headers.
 */
#[CoversClass( Request::class )]
class RequestTest extends TestCase {
	/**
	 * The `pre_option_home` filter forcing the home URL, if any.
	 */
	private ?Closure $home_url_filter = null;

	/**
	 * The plugin settings each test starts from.
	 *
	 * @var array<string,array<string,mixed>>
	 */
	private array $default_options = [
		'accessControl' => [
			'shouldBlockUnauthorizedDomains' => false,
			'hasSiteAddressInOrigin'         => false,
			'additionalAuthorizedDomains'    => [],
		],
		'cookies'       => [
			'hasAccessControlAllowCredentials' => false,
		],
	];

	/**
	 * {@inheritDoc}
	 */
	protected function setUp(): void {
		parent::setUp();

		update_option( AccessControlSettings::get_slug(), $this->default_options['accessControl'] );
		update_option( CookieSettings::get_slug(), $this->default_options['cookies'] );

		$this->reset_utils_properties();
		$this->clearSchema();
	}

	/**
	 * {@inheritDoc}
	 */
	protected function tearDown(): void {
		$this->clear_forced_home_url();
		delete_option( AccessControlSettings::get_slug() );
		delete_option( CookieSettings::get_slug() );
		unset( $_SERVER['HTTP_AUTHORIZATION'], $_SERVER['HTTP_ORIGIN'], $_SERVER['HTTP_REFERER'] );
		$GLOBALS['wp_rest_server'] = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$this->reset_utils_properties();

		parent::tearDown();
	}

	/**
	 * Tests that an invalid auth token is logged to the debug log.
	 */
	public function test_authenticate_token_on_request(): void {
		$debug_log = new DebugLog();

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
	}

	/**
	 * Tests that a missing origin is only rejected when `shouldBlockUnauthorizedDomains` is enabled.
	 */
	public function test_authenticate_origin_on_request_with_unauthorized_domain(): void {
		// Test with no origin set doesnt throw an error.
		Request::authenticate_origin_on_request();

		// Test with any origin set doesnt throw an error.
		$_SERVER['HTTP_ORIGIN'] = 'https://example.com';

		Request::authenticate_origin_on_request();

		// Test with shouldBlockUnauthorizedDomains set to true.
		update_option( AccessControlSettings::get_slug(), array_merge( $this->default_options['accessControl'], [ 'shouldBlockUnauthorizedDomains' => true ] ) );
		$this->reset_utils_properties();

		// If the origin is the WordPress address this should be fine.
		$_SERVER['HTTP_ORIGIN'] = site_url();

		Request::authenticate_origin_on_request();

		// If the origin isn't set, this should throw an error.
		unset( $_SERVER['HTTP_ORIGIN'], $_SERVER['HTTP_REFERER'] );

		$this->assert_origin_is_unauthorized();
	}

	/**
	 * Tests origin authentication when `hasSiteAddressInOrigin` is toggled.
	 */
	public function test_authenticate_origin_on_request_with_site_address(): void {
		$this->force_home_url( 'https://example.com' );
		update_option(
			AccessControlSettings::get_slug(),
			array_merge(
				$this->default_options['accessControl'],
				[
					'shouldBlockUnauthorizedDomains' => true,
					'hasSiteAddressInOrigin'         => true,
				]
			)
		);
		$this->reset_utils_properties();

		// Test with HTTP_ORIGIN set to the home.
		$_SERVER['HTTP_ORIGIN'] = 'https://example.com';

		// This will pass with hasSiteAddressInOrigin set to true.
		update_option(
			AccessControlSettings::get_slug(),
			array_merge(
				$this->default_options['accessControl'],
				[
					'shouldBlockUnauthorizedDomains' => true,
					'hasSiteAddressInOrigin'         => true,
				]
			)
		);
		$this->reset_utils_properties();

		Request::authenticate_origin_on_request();

		// This will fail with hasSiteAddressInOrigin set to false.
		update_option(
			AccessControlSettings::get_slug(),
			array_merge(
				$this->default_options['accessControl'],
				[
					'shouldBlockUnauthorizedDomains' => true,
					'hasSiteAddressInOrigin'         => false,
				]
			)
		);
		$this->reset_utils_properties();

		$this->assert_origin_is_unauthorized();
	}

	/**
	 * Tests origin authentication against `additionalAuthorizedDomains`.
	 */
	public function test_authenticate_origin_on_request_with_additional_domains(): void {
		update_option(
			AccessControlSettings::get_slug(),
			array_merge(
				$this->default_options['accessControl'],
				[
					'shouldBlockUnauthorizedDomains' => true,
					'additionalAuthorizedDomains'    => [
						'https://example.com',
					],
				]
			)
		);
		$this->reset_utils_properties();

		// Test with HTTP_ORIGIN set to the additional domain.
		$_SERVER['HTTP_ORIGIN'] = 'https://example.com';

		Request::authenticate_origin_on_request();

		// Test with a protocol mismatch will pass.
		$_SERVER['HTTP_ORIGIN'] = 'http://example.com';

		Request::authenticate_origin_on_request();

		// Test with a subdomain will fail.
		$_SERVER['HTTP_ORIGIN'] = 'https://subdomain.example.com';

		$this->assert_origin_is_unauthorized();

		// This will fail with additionalAuthorizedDomains set to false.
		update_option(
			AccessControlSettings::get_slug(),
			array_merge(
				$this->default_options['accessControl'],
				[
					'shouldBlockUnauthorizedDomains' => true,
					'additionalAuthorizedDomains'    => [],
				]
			)
		);
		$this->reset_utils_properties();

		$_SERVER['HTTP_ORIGIN'] = 'http://example.com';

		$this->assert_origin_is_unauthorized();
	}

	/**
	 * Tests that an origin port must match the authorized domain's port.
	 */
	public function test_authenticate_origin_on_request_with_different_ports(): void {
		// Test with different ports on the same domain.
		update_option(
			AccessControlSettings::get_slug(),
			array_merge(
				$this->default_options['accessControl'],
				[
					'shouldBlockUnauthorizedDomains' => true,
					'additionalAuthorizedDomains'    => [
						'http://example.com:3000',
					],
				]
			)
		);
		$this->reset_utils_properties();

		// Test it passes when matching.
		$_SERVER['HTTP_ORIGIN'] = 'http://example.com:3000';
		Request::authenticate_origin_on_request();

		// Test it fails when not matching ports.
		$_SERVER['HTTP_ORIGIN'] = 'http://example.com:8080';

		$this->assert_origin_is_unauthorized();
	}

	/**
	 * Tests that an origin subdomain must match the authorized domain's subdomain.
	 */
	public function test_authenticate_origin_on_request_with_different_subdomains(): void {
		// Test with different subdomains.
		update_option(
			AccessControlSettings::get_slug(),
			array_merge(
				$this->default_options['accessControl'],
				[
					'shouldBlockUnauthorizedDomains' => true,
					'additionalAuthorizedDomains'    => [
						'http://sub1.example.com',
					],
				]
			)
		);
		$this->reset_utils_properties();

		// Test it passes when matching.
		$_SERVER['HTTP_ORIGIN'] = 'http://sub1.example.com';
		Request::authenticate_origin_on_request();

		// Test it fails when not matching.
		$_SERVER['HTTP_ORIGIN'] = 'http://sub2.example.com';

		$this->assert_origin_is_unauthorized();
	}

	/**
	 * Tests the CORS and token response headers for the access control, cookie, and Site Token settings.
	 */
	public function test_response_headers_to_send(): void {
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
		$this->set_client_config( 'siteToken', $default_client_config );

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
			// Cache the result of preflight requests (600 is the upper limit for Chromium).
			'Content-Type'                  => 'application/json ; charset=' . get_option( 'blog_charset' ),
			'X-Robots-Tag'                  => 'noindex',
			'X-Content-Type-Options'        => 'nosniff',
			'X-GraphQL-URL'                 => graphql_get_endpoint_url(),
			'Vary'                          => 'X-Custom-Header',
		];

		$actual = Request::response_headers_to_send( $default_headers );

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
			CookieSettings::get_slug(),
			array_merge(
				$this->default_options['cookies'],
				[
					'hasAccessControlAllowCredentials' => true,
				]
			)
		);

		$this->reset_utils_properties();

		$actual = Request::response_headers_to_send( $default_headers );

		// Check Access-Control-Allow-Origin.
		$this->assertArrayHasKey( 'Access-Control-Allow-Origin', $actual );
		$this->assertStringContainsString( '*', $actual['Access-Control-Allow-Origin'] );

		// Check Access-Control-Allow-Credentials.
		$this->assertArrayNotHasKey( 'Access-Control-Allow-Credentials', $actual );

		// Test with hasAccessControlAllowCredentials and shouldBlockUnauthorizedDomains.
		update_option(
			AccessControlSettings::get_slug(),
			array_merge(
				$this->default_options['accessControl'],
				[
					'shouldBlockUnauthorizedDomains' => true,
				]
			)
		);
		update_option(
			CookieSettings::get_slug(),
			array_merge(
				$this->default_options['cookies'],
				[
					'hasAccessControlAllowCredentials' => true,
				]
			)
		);

		$this->reset_utils_properties();

		$actual = Request::response_headers_to_send( $default_headers );

		// Check Access-Control-Allow-Origin falls back to the WordPress address.
		$this->assertArrayHasKey( 'Access-Control-Allow-Origin', $actual );
		$this->assertSame( site_url(), $actual['Access-Control-Allow-Origin'] );

		// Check Access-Control-Allow-Credentials.
		$this->assertArrayHasKey( 'Access-Control-Allow-Credentials', $actual );

		// Test with custom headers and explicit origin.
		$default_client_config['isEnabled'] = true;
		$this->set_client_config( 'siteToken', $default_client_config );
		$_SERVER['HTTP_ORIGIN'] = site_url();

		update_option(
			AccessControlSettings::get_slug(),
			array_merge(
				$this->default_options['accessControl'],
				[
					'customHeaders' => [
						'X-Custom-Header',
						'X-Custom-Header-2',
					],
				]
			)
		);
		$this->reset_utils_properties();

		$actual = Request::response_headers_to_send( $default_headers );

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

		// Test with authenticated user and shouldBlockUnauthorizedDomains.
		$user_id = $this->factory()->user->create(
			[
				'role' => 'administrator',
			]
		);

		update_option(
			AccessControlSettings::get_slug(),
			array_merge(
				$this->default_options['accessControl'],
				[
					'shouldBlockUnauthorizedDomains' => true,
				]
			)
		);

		$tokens = $this->generate_user_tokens( $user_id );

		$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $tokens['auth_token'];

		$actual = Request::response_headers_to_send( $default_headers );

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

	/**
	 * Tests the `Access-Control-Allow-Origin` header when unauthorized domains are blocked.
	 */
	public function test_response_headers_to_send_sets_acao_header(): void {
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
			// Cache the result of preflight requests (600 is the upper limit for Chromium).
			'Content-Type'                  => 'application/json ; charset=' . get_option( 'blog_charset' ),
			'X-Robots-Tag'                  => 'noindex',
			'X-Content-Type-Options'        => 'nosniff',
			'X-GraphQL-URL'                 => graphql_get_endpoint_url(),
		];

		// Test with custom headers.
		update_option(
			AccessControlSettings::get_slug(),
			array_merge(
				$this->default_options['accessControl'],
				[
					'shouldBlockUnauthorizedDomains' => true,
				]
			)
		);
		$this->reset_utils_properties();

		// Test with shouldBlockUnauthorizedDomains set to true.
		$actual = Request::response_headers_to_send( $default_headers );

		$this->assertArrayHasKey( 'Access-Control-Allow-Origin', $actual );
		$this->assertStringContainsString( site_url(), $actual['Access-Control-Allow-Origin'] );

		// Test external origin doesnt change ACAO header.
		$_SERVER['HTTP_ORIGIN'] = 'https://example.com';

		$actual = Request::response_headers_to_send( $default_headers );

		$this->assertArrayHasKey( 'Access-Control-Allow-Origin', $actual );
		$this->assertStringContainsString( site_url(), $actual['Access-Control-Allow-Origin'] );

		// Test with hasSiteAddressInOrigin set to true.
		$this->force_home_url( 'https://example.com' );
		update_option(
			AccessControlSettings::get_slug(),
			array_merge(
				$this->default_options['accessControl'],
				[
					'shouldBlockUnauthorizedDomains' => true,
					'hasSiteAddressInOrigin'         => true,
				]
			)
		);
		$this->reset_utils_properties();

		$actual = Request::response_headers_to_send( $default_headers );

		$this->assertArrayHasKey( 'Access-Control-Allow-Origin', $actual );
		$this->assertStringContainsString( home_url(), $actual['Access-Control-Allow-Origin'] );

		// Test with additionalAuthorizedDomains.
		update_option(
			AccessControlSettings::get_slug(),
			array_merge(
				$this->default_options['accessControl'],
				[
					'shouldBlockUnauthorizedDomains' => true,
					'hasSiteAddressInOrigin'         => true,
					'additionalAuthorizedDomains'    => [
						'https://example2.com',
					],
				]
			)
		);
		$this->reset_utils_properties();

		$_SERVER['HTTP_ORIGIN'] = 'https://example2.com';

		$actual = Request::response_headers_to_send( $default_headers );

		$this->assertArrayHasKey( 'Access-Control-Allow-Origin', $actual );
		$this->assertStringContainsString( 'https://example2.com', $actual['Access-Control-Allow-Origin'] );
	}

	/**
	 * Tests that an origin allowed by an upstream `Access-Control-Allow-Origin` header is kept.
	 */
	public function test_response_headers_to_send_allows_upstream_origin(): void {
		update_option(
			AccessControlSettings::get_slug(),
			array_merge(
				$this->default_options['accessControl'],
				[
					'shouldBlockUnauthorizedDomains' => true,
					'additionalAuthorizedDomains'    => [ 'https://example2.com' ],
				]
			)
		);
		$this->reset_utils_properties();

		$headers = [ 'Access-Control-Allow-Origin' => 'https://frontend.example.com' ];

		$_SERVER['HTTP_ORIGIN'] = 'https://frontend.example.com';

		$actual = Request::response_headers_to_send( $headers );

		$this->assertSame( 'https://frontend.example.com', $actual['Access-Control-Allow-Origin'] );

		$_SERVER['HTTP_ORIGIN'] = 'https://example2.com';

		$actual = Request::response_headers_to_send( $headers );

		$this->assertSame( 'https://example2.com', $actual['Access-Control-Allow-Origin'] );

		// Unauthorized origins fall back to the site URL.
		$_SERVER['HTTP_ORIGIN'] = 'https://unauthorized.example.com';

		$actual = Request::response_headers_to_send( $headers );

		$this->assertSame( site_url(), $actual['Access-Control-Allow-Origin'] );
	}

	/**
	 * Tests that refreshed tokens are only sent in the response headers over SSL or when debugging.
	 */
	public function test_response_headers_to_send_omits_tokens_without_ssl_or_debugging(): void {
		$tokens = $this->generate_user_tokens( $this->factory()->user->create() );

		$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $tokens['auth_token'];

		$this->assertArrayHasKey( 'X-WPGraphQL-Login-Token', Request::response_headers_to_send( [] ) );

		add_filter( 'graphql_debug_enabled', '__return_false', 100 );

		$actual = Request::response_headers_to_send( [] );

		$this->assertArrayNotHasKey( 'X-WPGraphQL-Login-Token', $actual );
		$this->assertArrayNotHasKey( 'X-WPGraphQL-Login-Refresh-Token', $actual );
	}

	/**
	 * Tests that REST responses get the CORS and refreshed token headers, but only when served over SSL or debugging.
	 */
	public function test_rest_responses_get_login_headers(): void {
		global $wp_rest_server;

		$wp_rest_server = new \WP_REST_Server();
		do_action( 'rest_api_init' );

		$user_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
		$tokens  = $this->generate_user_tokens( $user_id );

		$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $tokens['auth_token'];

		// Debugging is enabled in the tests.
		$headers = $wp_rest_server->dispatch( new \WP_REST_Request( 'GET', '/wp/v2/types/post' ) )->get_headers();

		$this->assertStringContainsString( 'X-WPGraphQL-Login-Token', $headers['Access-Control-Allow-Headers'] ?? '' );
		$this->assertSame( $user_id, TokenManager::validate_token( $headers['X-WPGraphQL-Login-Token'] ?? '' )->data->user->id );
		$this->assertSame( $user_id, TokenManager::validate_token( $headers['X-WPGraphQL-Login-Refresh-Token'] ?? '', true )->data->user->id );

		// Errors are passed through.
		$response = $wp_rest_server->dispatch( new \WP_REST_Request( 'GET', '/wp/v2/types/not-a-type' ) );

		$this->assertSame( 404, $response->get_status() );
		$this->assertArrayNotHasKey( 'Access-Control-Allow-Headers', $response->get_headers() );

		// Tokens aren't refreshed for deleted users.
		require_once ABSPATH . 'wp-admin/includes/user.php';
		wp_delete_user( $user_id );

		$headers = $wp_rest_server->dispatch( new \WP_REST_Request( 'GET', '/wp/v2/types/post' ) )->get_headers();

		$this->assertArrayHasKey( 'Access-Control-Allow-Headers', $headers );
		$this->assertArrayNotHasKey( 'X-WPGraphQL-Login-Token', $headers );

		// Without SSL or debugging, the headers aren't added.
		add_filter( 'graphql_debug_enabled', '__return_false', 100 );

		$headers = $wp_rest_server->dispatch( new \WP_REST_Request( 'GET', '/wp/v2/types/post' ) )->get_headers();

		$this->assertArrayNotHasKey( 'Access-Control-Allow-Headers', $headers );
		$this->assertArrayNotHasKey( 'X-WPGraphQL-Login-Token', $headers );
	}

	/**
	 * Tests that an invalid auth token gets a 403 with a debug message, while still resolving public data.
	 */
	public function test_query_with_invalid_auth_token_returns_public_data_and_debug_message(): void {
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

	/**
	 * Tests that a request without an auth token resolves public data without token headers.
	 */
	public function test_query_without_auth_token_returns_public_data_without_token_headers(): void {
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
		$GLOBALS['current_user']       = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Forces WordPress to re-determine the current user.

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

	/**
	 * Asserts that the current request origin is rejected as unauthorized.
	 */
	private function assert_origin_is_unauthorized(): void {
		try {
			Request::authenticate_origin_on_request();
		} catch ( UserError $error ) {
			$this->assertSame( 'Unauthorized request origin.', $error->getMessage() );

			return;
		}

		$this->fail( 'Expected an unauthorized request origin error.' );
	}

	/**
	 * Forces the home URL for the remainder of the test.
	 *
	 * @param string $home_url The home URL to return from `pre_option_home`.
	 */
	private function force_home_url( string $home_url ): void {
		$this->clear_forced_home_url();

		$this->home_url_filter = static fn (): string => $home_url;
		add_filter( 'pre_option_home', $this->home_url_filter );
	}

	/**
	 * Removes the forced home URL, if any.
	 */
	private function clear_forced_home_url(): void {
		if ( null === $this->home_url_filter ) {
			return;
		}

		remove_filter( 'pre_option_home', $this->home_url_filter );
		$this->home_url_filter = null;
	}
}
