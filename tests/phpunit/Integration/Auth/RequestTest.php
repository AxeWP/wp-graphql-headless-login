<?php
/**
 * Tests the Auth\Request class.
 *
 * @package Tests\WPGraphQL\Login\Integration\Auth
 */

namespace Tests\WPGraphQL\Login\Integration\Auth;

use PHPUnit\Framework\Attributes\CoversClass;
use Tests\WPGraphQL\Login\TestCase;
use WPGraphQL\Login\Admin\Settings\AccessControlSettings;
use WPGraphQL\Login\Admin\Settings\CookieSettings;
use WPGraphQL\Login\Auth\Request;
use WPGraphQL\Login\Auth\TokenManager;

/**
 * Test Auth\Request class
 */
#[CoversClass( Request::class )]
class RequestTest extends TestCase {
	/**
	 * The home URL to force via the `pre_option_home` filter.
	 */
	private ?string $forced_home_url = null;

	/**
	 * The home URL before the test ran.
	 */
	private string $original_home_url;

	/**
	 * The `iss` values of the tokens minted during the test.
	 */
	private array $token_issuers = [];

	/**
	 * The plugin settings each test starts from.
	 *
	 * @var array<string,mixed>
	 */
	public $default_options = [
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
	 * Wrapper for `get_option( 'blog_charset' )`.
	 */
	private function getBlogCharset(): string {
		return (string) call_user_func( 'get_option', 'blog_charset' );
	}

	/**
	 * Wrapper for `home_url()`.
	 */
	private function getHomeUrl(): string {
		return (string) call_user_func( 'home_url' );
	}

	/**
	 * Wrapper for `site_url()`.
	 */
	private function getSiteUrl(): string {
		return (string) site_url();
	}

	/**
	 * Returns the `iss` claim from the given token.
	 */
	private function getTokenIssuer( string $token ): ?string {
		$parts = explode( '.', $token );

		if ( 3 !== count( $parts ) ) {
			return null;
		}

		$payload = json_decode( (string) base64_decode( strtr( $parts[1], '-_', '+/' ) ) );

		return isset( $payload->iss ) && is_string( $payload->iss ) ? $payload->iss : null;
	}

	/**
	 * Adds the test site URLs and minted token issuers to the allowed `iss` domains.
	 */
	public function filterAllowedIssDomains( array $allowed_domains ): array {
		$home_url = $this->forced_home_url ?? $this->getHomeUrl();

		return array_values(
			array_filter(
				array_unique(
					array_merge(
						$allowed_domains,
						[
							$home_url,
							$this->getSiteUrl(),
							str_replace( 'http://', 'https://', $home_url ),
							str_replace( 'https://', 'http://', $home_url ),
							...$this->token_issuers,
						]
					)
				)
			)
		);
	}

	/**
	 * Filters `pre_option_home` to return the forced home URL.
	 */
	public function filterHomeOption( $pre_option ) {
		return $this->forced_home_url ?? $pre_option;
	}

	/**
	 * Forces the home URL for the remainder of the test.
	 */
	private function forceHomeUrl( string $home_url ): void {
		$this->forced_home_url = $home_url;
		add_filter( 'pre_option_home', [ $this, 'filterHomeOption' ] );
	}

	/**
	 * Stops forcing the home URL.
	 */
	private function clearForcedHomeUrl(): void {
		$this->forced_home_url = null;
		call_user_func( 'remove_filter', 'pre_option_home', [ $this, 'filterHomeOption' ] );
	}

	/**
	 * {@inheritDoc}
	 */
	public function setUp(): void {
		parent::setUp();

		$this->original_home_url = $this->getHomeUrl();
		call_user_func( 'add_filter', 'graphql_login_iss_allowed_domains', [ $this, 'filterAllowedIssDomains' ] );

		update_option( AccessControlSettings::get_slug(), $this->default_options['accessControl'] );
		update_option( CookieSettings::get_slug(), $this->default_options['cookies'] );

		$this->reset_utils_properties();
	}

	/**
	 * {@inheritDoc}
	 */
	public function tearDown(): void {
		$this->clearForcedHomeUrl();
		call_user_func( 'remove_filter', 'graphql_login_iss_allowed_domains', [ $this, 'filterAllowedIssDomains' ] );
		update_option( 'home', $this->original_home_url );
		delete_option( AccessControlSettings::get_slug() );
		delete_option( CookieSettings::get_slug() );
		unset( $_SERVER['HTTP_AUTHORIZATION'], $_SERVER['HTTP_ORIGIN'], $_SERVER['HTTP_REFERER'] );
		$this->reset_utils_properties();

		parent::tearDown();
	}

	/**
	 * Tests `Request::authenticate_token_on_request()`.
	 */
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

	/**
	 * Tests origin authentication against an unauthorized domain.
	 */
	public function testAuthenticateOriginOnRequestWithUnauthorizedDomain() {
		// Test with no origin set doesnt throw an error.
		Request::authenticate_origin_on_request();

		// Test with any origin set doesnt throw an error.
		$_SERVER['HTTP_ORIGIN'] = 'https://example.com';

		Request::authenticate_origin_on_request();

		// Test with shouldBlockUnauthorizedDomains set to true.

		update_option( AccessControlSettings::get_slug(), array_merge( $this->default_options['accessControl'], [ 'shouldBlockUnauthorizedDomains' => true ] ) );
		$this->reset_utils_properties();

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

	/**
	 * Tests origin authentication when `hasSiteAddressInOrigin` is toggled.
	 */
	public function testAuthenticateOriginOnRequestWithSiteAddress() {
		$this->forceHomeUrl( 'https://example.com' );
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

		$this->expectException( \GraphQL\Error\UserError::class );
		$this->expectExceptionMessage( 'Unauthorized request origin.' );

		Request::authenticate_origin_on_request();

		// cleanup
		unset( $_SERVER['HTTP_ORIGIN'] );
		$this->clearForcedHomeUrl();
		unset( $_SERVER['HTTP_AUTHORIZATION'] );
	}

	/**
	 * Tests origin authentication against `additionalAuthorizedDomains`.
	 */
	public function testAuthenticateOriginOnRequestWithAdditionalDomains() {
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

		$this->expectException( \GraphQL\Error\UserError::class );
		$this->expectExceptionMessage( 'Unauthorized request origin.' );

		Request::authenticate_origin_on_request();

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

		$this->expectException( \GraphQL\Error\UserError::class );
		$this->expectExceptionMessage( 'Unauthorized request origin.' );

		$_SERVER['HTTP_ORIGIN'] = 'http://example.com';

		Request::authenticate_origin_on_request();

		unset( $_SERVER['HTTP_ORIGIN'] );
	}

	/**
	 * Test authenticate_origin_on_request with different ports.
	 */
	public function testAuthenticateOriginOnRequestWithDifferentPorts(): void {
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

		$this->expectException( \GraphQL\Error\UserError::class );
		$this->expectExceptionMessage( 'Unauthorized request origin.' );

		Request::authenticate_origin_on_request();

		// Cleanup.
		unset( $_SERVER['HTTP_ORIGIN'] );
	}

	/**
	 * Test authenticate_origin_on_request with different subdomains.
	 */
	public function testAuthenticateOriginOnRequestWithDifferentSubdomains(): void {
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

		$this->expectException( \GraphQL\Error\UserError::class );
		$this->expectExceptionMessage( 'Unauthorized request origin.' );

		Request::authenticate_origin_on_request();

		// Cleanup.
		unset( $_SERVER['HTTP_ORIGIN'] );
	}

	/**
	 * Tests `Request::response_headers_to_send()`.
	 */
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
			// cache the result of preflight requests (600 is the upper limit for Chromium).
			'Content-Type'                  => 'application/json ; charset=' . $this->getBlogCharset(),
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

		// Check Access-Control-Allow-Origin.
		$this->assertArrayHasKey( 'Access-Control-Allow-Origin', $actual );
		$this->assertStringContainsString( '', $actual['Access-Control-Allow-Origin'] );

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

		// Test with authenticated user and shouldBlockUnauthorizedDomains
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
		$issuer = $this->getTokenIssuer( $tokens['auth_token'] );

		if ( null !== $issuer ) {
			$this->token_issuers[] = $issuer;
		}

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
	 * Tests the `Access-Control-Allow-Origin` header.
	 */
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
			'Content-Type'                  => 'application/json ; charset=' . $this->getBlogCharset(),
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
		$this->assertStringContainsString( $this->getSiteUrl(), $actual['Access-Control-Allow-Origin'] );

		// Test external origin doesnt change ACAO header.
		$_SERVER['HTTP_ORIGIN'] = 'https://example.com';

		$actual = Request::response_headers_to_send( $default_headers );

		$this->assertArrayHasKey( 'Access-Control-Allow-Origin', $actual );
		$this->assertStringContainsString( $this->getSiteUrl(), $actual['Access-Control-Allow-Origin'] );

		// Test with hasSiteAddressInOrigin set to true.
		$this->forceHomeUrl( 'https://example.com' );
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
		$this->assertStringContainsString( $this->getHomeUrl(), $actual['Access-Control-Allow-Origin'] );

		// Test with additionalAuthorizedDomains
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
}
