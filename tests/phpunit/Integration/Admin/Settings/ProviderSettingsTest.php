<?php
/**
 * Tests the provider settings.
 *
 * @package WPGraphQL\Login\Tests\Integration\Admin\Settings
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Tests\Integration\Admin\Settings;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use WPGraphQL\Login\Admin\Admin;
use WPGraphQL\Login\Admin\Settings\ProviderSettings;
use WPGraphQL\Login\Auth\ProviderConfig\OAuth2\Facebook;
use WPGraphQL\Login\Auth\ProviderConfig\OAuth2\Generic;
use WPGraphQL\Login\Auth\ProviderConfig\OAuth2\GitHub;
use WPGraphQL\Login\Auth\ProviderConfig\OAuth2\Google;
use WPGraphQL\Login\Auth\ProviderConfig\OAuth2\Instagram;
use WPGraphQL\Login\Auth\ProviderConfig\OAuth2\LinkedIn;
use WPGraphQL\Login\Auth\ProviderConfig\OAuth2\OAuth2Config;
use WPGraphQL\Login\Auth\ProviderConfig\Password;
use WPGraphQL\Login\Auth\ProviderConfig\ProviderConfig;
use WPGraphQL\Login\Auth\ProviderConfig\SiteToken;
use WPGraphQL\Login\Auth\ProviderRegistry;
use WPGraphQL\Login\Tests\TestCase;
use WPGraphQL\Login\Utils\Utils;
use WP_REST_Request;
use WP_REST_Server;

/**
 * Tests that the provider settings are registered to the WP REST settings endpoint, which the settings app uses to save them.
 */
#[CoversClass( ProviderSettings::class )]
#[CoversClass( Admin::class )]
#[CoversClass( ProviderConfig::class )]
#[CoversClass( OAuth2Config::class )]
#[CoversClass( Facebook::class )]
#[CoversClass( Generic::class )]
#[CoversClass( GitHub::class )]
#[CoversClass( Google::class )]
#[CoversClass( Instagram::class )]
#[CoversClass( LinkedIn::class )]
#[CoversClass( Password::class )]
#[CoversClass( SiteToken::class )]
class ProviderSettingsTest extends TestCase {
	/**
	 * The REST server instance.
	 */
	private WP_REST_Server $server;

	/**
	 * {@inheritDoc}
	 */
	protected function setUp(): void {
		parent::setUp();

		// Rebuild the memoized settings config, and register it.
		$reflection = new ReflectionClass( ProviderSettings::class );
		$reflection->setStaticPropertyValue( 'config', [] );
		$reflection->setStaticPropertyValue( 'args', [] );

		( new Admin() )->register_provider_settings();

		global $wp_rest_server;

		$wp_rest_server = new WP_REST_Server();
		$this->server   = $wp_rest_server;
		do_action( 'rest_api_init' );

		wp_set_current_user( $this->factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function tearDown(): void {
		global $wp_rest_server;
		$wp_rest_server = null;

		$this->reset_utils_properties();

		parent::tearDown();
	}

	/**
	 * Tests that every registered provider is exposed with its default settings.
	 */
	public function test_provider_settings_are_exposed_with_defaults(): void {
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wp/v2/settings' ) );

		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();

		foreach ( ProviderRegistry::get_instance()->get_registered_providers() as $slug => $provider ) {
			$key = ProviderSettings::$settings_prefix . $slug;

			$this->assertArrayHasKey( $key, $data );
			$this->assertSame( $provider::get_name(), $data[ $key ]['name'] );
			$this->assertSame( $slug, $data[ $key ]['slug'] );
			$this->assertSame( 0, $data[ $key ]['order'] );
			$this->assertFalse( $data[ $key ]['isEnabled'] );
		}
	}

	/**
	 * Tests that the provider-specific options can be saved.
	 */
	public function test_provider_settings_can_be_saved(): void {
		$expected = [
			'name'          => 'Sign in with Google',
			'order'         => 2,
			'slug'          => 'google',
			'isEnabled'     => true,
			'clientOptions' => [
				'clientId'     => 'mock_client_id',
				'clientSecret' => 'mock_client_secret',
				'redirectUri'  => 'https://example.com/callback',
				'hostedDomain' => 'example.com',
				'promptType'   => 'consent',
				'scope'        => [ 'openid', 'email' ],
			],
			'loginOptions'  => [
				'useAuthenticationCookie' => true,
				'createUserIfNoneExists'  => false,
				'linkExistingUsers'       => true,
			],
		];

		$response = $this->update_settings( [ 'wpgraphql_login_provider_google' => $expected ] );

		$this->assertSame( 200, $response->get_status() );
		$this->assertEquals( $expected, $response->get_data()['wpgraphql_login_provider_google'] );

		$this->reset_utils_properties();
		$this->assertEquals( $expected, Utils::get_provider_settings( 'google' ) );
	}

	/**
	 * Tests that saved provider settings are sanitized, dropping any options the provider doesn't support.
	 */
	public function test_provider_settings_are_sanitized_on_save(): void {
		update_option(
			ProviderSettings::$settings_prefix . 'instagram',
			[
				'name'          => '<b>Instagram</b>',
				'order'         => '3',
				'isEnabled'     => '1',
				'notASetting'   => 'value',
				'clientOptions' => [
					'clientId'    => 'mock_client_id',
					'notAnOption' => 'value',
				],
				'loginOptions'  => [
					'useAuthenticationCookie' => 1,
					'createUserIfNoneExists'  => true,
					// Instagram doesn't return an email address to link existing users with.
					'linkExistingUsers'       => true,
				],
			]
		);

		$this->assertSame(
			[
				'name'          => 'Instagram',
				'order'         => 3,
				'isEnabled'     => true,
				'clientOptions' => [
					'clientId' => 'mock_client_id',
				],
				'loginOptions'  => [
					'useAuthenticationCookie' => true,
					'createUserIfNoneExists'  => true,
				],
			],
			get_option( ProviderSettings::$settings_prefix . 'instagram' )
		);

		update_option( ProviderSettings::$settings_prefix . 'instagram', 'not-an-array' );

		$this->assertSame( [], get_option( ProviderSettings::$settings_prefix . 'instagram' ) );
	}

	/**
	 * Tests that the options are validated against each provider's schema.
	 *
	 * @param string              $slug     The provider slug.
	 * @param array<string,mixed> $settings The invalid provider settings.
	 */
	#[DataProvider( 'invalid_settings_provider' )]
	public function test_provider_settings_are_validated_against_provider_schema( string $slug, array $settings ): void {
		$response = $this->update_settings( [ ProviderSettings::$settings_prefix . $slug => $settings ] );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'rest_invalid_param', $response->get_data()['code'] );

		$this->reset_utils_properties();
		$this->assertSame( [], Utils::get_provider_settings( $slug ), 'Nothing should be saved.' );
	}

	/**
	 * Provides invalid settings for each provider.
	 *
	 * @return array<string,array{0:string,1:array<string,mixed>}>
	 */
	public static function invalid_settings_provider(): array {
		return [
			'non-boolean isEnabled'             => [ 'password', [ 'isEnabled' => 'maybe' ] ],
			'non-boolean login cookie'          => [ 'password', [ 'loginOptions' => [ 'useAuthenticationCookie' => 'maybe' ] ] ],
			'non-string OAuth2 client ID'       => [ 'github', [ 'clientOptions' => [ 'clientId' => [ 'not-a-string' ] ] ] ],
			'non-boolean OAuth2 login option'   => [ 'github', [ 'loginOptions' => [ 'linkExistingUsers' => 'maybe' ] ] ],
			'malformed Facebook API version'    => [ 'facebook', [ 'clientOptions' => [ 'graphAPIVersion' => 'latest' ] ] ],
			'non-boolean Facebook beta tier'    => [ 'facebook', [ 'clientOptions' => [ 'enableBetaTier' => 'maybe' ] ] ],
			'non-string Generic authorize URL'  => [ 'oauth2-generic', [ 'clientOptions' => [ 'urlAuthorize' => [ 'not-a-string' ] ] ] ],
			'non-string GitHub scope'           => [ 'github', [ 'clientOptions' => [ 'scope' => [ [ 'not-a-string' ] ] ] ] ],
			'unknown Google prompt type'        => [ 'google', [ 'clientOptions' => [ 'promptType' => 'always' ] ] ],
			'non-string Instagram scope'        => [ 'instagram', [ 'clientOptions' => [ 'scope' => [ [ 'not-a-string' ] ] ] ] ],
			'non-string LinkedIn scope'         => [ 'linkedin', [ 'clientOptions' => [ 'scope' => [ [ 'not-a-string' ] ] ] ] ],
			'non-string Site Token header key'  => [ 'siteToken', [ 'clientOptions' => [ 'headerKey' => [ 'not-a-string' ] ] ] ],
			'non-string Site Token user lookup' => [ 'siteToken', [ 'loginOptions' => [ 'metaKey' => [ 'not-a-string' ] ] ] ],
		];
	}

	/**
	 * Updates the settings through the WP REST API.
	 *
	 * @param array<string,mixed> $values The settings to update.
	 */
	private function update_settings( array $values ): \WP_REST_Response {
		$request = new WP_REST_Request( 'POST', '/wp/v2/settings' );
		$request->add_header( 'Content-Type', 'application/json' );
		$request->set_body( (string) wp_json_encode( $values ) );

		return rest_ensure_response( $this->server->dispatch( $request ) );
	}
}
