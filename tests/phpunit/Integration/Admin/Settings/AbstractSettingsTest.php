<?php
/**
 * Tests the plugin settings groups.
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
use WPGraphQL\Login\Admin\SettingsRegistry;
use WPGraphQL\Login\Tests\TestCase;

/**
 * Tests the AbstractSettings implementations.
 */
#[CoversClass( AbstractSettings::class )]
#[CoversClass( AccessControlSettings::class )]
#[CoversClass( CookieSettings::class )]
#[CoversClass( PluginSettings::class )]
class AbstractSettingsTest extends TestCase {
	/**
	 * {@inheritDoc}
	 */
	protected function setUp(): void {
		parent::setUp();

		// Re-register the settings, so they're built from a fresh config.
		foreach ( SettingsRegistry::get_all() as $setting ) {
			unregister_setting( AbstractSettings::SETTINGS_GROUP, $setting::get_slug() );
			$setting->register();
		}
	}

	/**
	 * Tests that the stored settings fall back to their configured defaults.
	 */
	public function test_registered_settings_default_to_config_values(): void {
		$this->assertSame(
			[
				'hasAccessControlAllowCredentials' => false,
				'hasLogoutMutation'                => false,
				'sameSiteOption'                   => 'Lax',
				'cookieDomain'                     => '',
			],
			get_option( CookieSettings::get_slug() )
		);
	}

	/**
	 * Tests that saved settings are sanitized, dropping any keys not in the config.
	 */
	public function test_registered_settings_are_sanitized_on_save(): void {
		update_option(
			CookieSettings::get_slug(),
			[
				'hasLogoutMutation' => '1',
				'cookieDomain'      => '<b>.example.com</b>',
				'notASetting'       => 'value',
			]
		);

		$this->assertSame(
			[
				'hasLogoutMutation' => true,
				'cookieDomain'      => '.example.com',
			],
			get_option( CookieSettings::get_slug() )
		);
	}

	/**
	 * Tests that unsupported wildcards are dropped from the authorized domains when saved outside the REST API.
	 */
	public function test_authorized_domains_drop_wildcards(): void {
		update_option(
			AccessControlSettings::get_slug(),
			[ 'additionalAuthorizedDomains' => [ '*', 'https://example.com', ' * ' ] ]
		);

		$this->assertSame(
			[ 'additionalAuthorizedDomains' => [ 'https://example.com' ] ],
			get_option( AccessControlSettings::get_slug() )
		);
	}

	/**
	 * Tests that saving a value that isn't a settings object discards it.
	 */
	public function test_registered_settings_discard_non_array_values(): void {
		update_option( CookieSettings::get_slug(), 'not-an-array' );

		$this->assertSame( [], get_option( CookieSettings::get_slug() ) );
		$this->assertSame( [], SettingsRegistry::get( CookieSettings::get_slug() )->get_values() );
	}

	/**
	 * Tests that update_values() merges the new values into the stored ones.
	 */
	public function test_update_values_merges_with_stored_values(): void {
		$settings = SettingsRegistry::get( CookieSettings::get_slug() );

		update_option( CookieSettings::get_slug(), [ 'cookieDomain' => '.example.com' ] );

		$settings->update_values( [ 'sameSiteOption' => 'Strict' ] );

		$this->assertSame(
			[
				'cookieDomain'   => '.example.com',
				'sameSiteOption' => 'Strict',
			],
			$settings->get_values()
		);
	}

	/**
	 * Tests that the config passed to the settings app describes each group without exposing the PHP callbacks.
	 */
	public function test_render_config_excludes_callbacks(): void {
		foreach ( SettingsRegistry::get_all() as $slug => $setting ) {
			$actual = $setting->get_render_config();

			$this->assertNotEmpty( $actual['title'], $slug . ' should have a title.' );
			$this->assertNotEmpty( $actual['label'], $slug . ' should have a label.' );
			$this->assertNotEmpty( $actual['description'], $slug . ' should have a description.' );
			$this->assertSame( array_keys( $setting->get_config() ), array_keys( $actual['fields'] ) );

			foreach ( $actual['fields'] as $key => $field ) {
				$this->assertArrayNotHasKey( 'sanitize_callback', $field, sprintf( '%s.%s should not expose its sanitize callback.', $slug, $key ) );
				$this->assertArrayNotHasKey( 'validate_callback', $field, sprintf( '%s.%s should not expose its validate callback.', $slug, $key ) );
				$this->assertNotEmpty( $field['label'], sprintf( '%s.%s should have a label.', $slug, $key ) );
			}
		}
	}

	/**
	 * Tests that prepare_value() rejects unknown settings and sanitizes known ones.
	 */
	public function test_prepare_value(): void {
		$settings = SettingsRegistry::get( CookieSettings::get_slug() );

		$actual = $settings->prepare_value( 'notASetting', 'value' );

		$this->assertWPError( $actual );
		$this->assertSame( 'invalid_setting_key', $actual->get_error_code() );

		$this->assertTrue( $settings->prepare_value( 'hasLogoutMutation', 'true' ) );
	}
}
