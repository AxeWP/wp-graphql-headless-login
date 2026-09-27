<?php
/**
 * Shared helpers for the plugin tests.
 *
 * @package Tests\WPGraphQL\Login\Traits
 */

namespace Tests\WPGraphQL\Login\Traits;

use ReflectionClass;
use WPGraphQL\Login\Admin\Settings\ProviderSettings;
use WPGraphQL\Login\Auth\TokenManager;
use WPGraphQL\Login\Utils\Utils;

/**
 * Trait - TestHelperTrait
 */
trait TestHelperTrait {
	/**
	 * Resets the memoized static properties on the Utils class.
	 */
	public function reset_utils_properties(): void {
		$reflection = new ReflectionClass( 'WPGraphQL\Login\Utils\Utils' );

		$property = $reflection->getProperty( 'providers' );
		$property->setAccessible( true );
		$property->setValue( null, [] );

		$property = $reflection->getProperty( 'settings' );
		$property->setAccessible( true );
		$property->setValue( null, [] );

		$property = $reflection->getProperty( 'access_control' );
		$property->setAccessible( true );
		$property->setValue( null, null );
	}

	/**
	 * Resets the ProviderRegistry singleton.
	 */
	public function reset_provider_registry(): void {
		$reflection = new ReflectionClass( 'WPGraphQL\Login\Auth\ProviderRegistry' );
		$property   = $reflection->getProperty( 'instance' );
		$property->setAccessible( true );
		$property->setValue( null, null );
	}

	/**
	 * Resets the registered GraphQL types.
	 */
	public function reset_type_registry(): void {
		$reflection = new ReflectionClass( 'WPGraphQL\Login\GraphQL\TypeRegistry' );
		$property   = $reflection->getProperty( 'registry' );
		$property->setAccessible( true );
		$property->setValue( null, [] );
	}

	/**
	 * Sets the provider settings for the given slug.
	 */
	public function set_client_config( string $slug, array $config ): void {
		update_option( ProviderSettings::$settings_prefix . $slug, $config );
		$this->reset_utils_properties();
		$this->reset_provider_registry();
	}

	/**
	 * Clears the provider settings for the given slug.
	 */
	public function clear_client_config( string $slug ): void {
		update_option( ProviderSettings::$settings_prefix . $slug, [] );
		$this->reset_utils_properties();
		$this->reset_provider_registry();
	}

	/**
	 * Issues a new user secret, returning it along with the user auth and refresh tokens.
	 */
	public function generate_user_tokens( int $user_id ): array {
		$original_user = get_current_user_id();
		wp_set_current_user( $user_id );

		$site_secret = wp_generate_password( 64, false, false );
		Utils::update_plugin_setting( 'jwt_secret_key', $site_secret );
		TokenManager::issue_new_user_secret( $user_id, false );
		$this->reset_utils_properties();

		$auth_token = TokenManager::get_auth_token( wp_get_current_user(), false );
		$this->reset_utils_properties();

		$refresh_token = TokenManager::get_refresh_token( wp_get_current_user(), false );
		$this->reset_utils_properties();

		wp_set_current_user( $original_user );

		return [
			'site_secret'   => $site_secret,
			'auth_token'    => $auth_token,
			'refresh_token' => $refresh_token,
		];
	}

	/**
	 * Runs the callback, returning its response along with the auth cookie events it fired.
	 *
	 * @return array{response:mixed,events:array{set_auth_cookie:list<array<int,mixed>>,set_logged_in_cookie:list<array<int,mixed>>}}
	 */
	public function capture_auth_cookie_events( callable $callback ): array {
		$events           = [
			'set_auth_cookie'      => [],
			'set_logged_in_cookie' => [],
		];
		$auth_cookie_hook = static function ( ...$args ) use ( &$events ): void {
			$events['set_auth_cookie'][] = $args;
		};
		$logged_in_hook   = static function ( ...$args ) use ( &$events ): void {
			$events['set_logged_in_cookie'][] = $args;
		};

		add_action( 'set_auth_cookie', $auth_cookie_hook, 10, 6 );
		add_action( 'set_logged_in_cookie', $logged_in_hook, 10, 6 );

		try {
			$response = $callback();
		} finally {
			remove_action( 'set_auth_cookie', $auth_cookie_hook );
			remove_action( 'set_logged_in_cookie', $logged_in_hook );
		}

		return [
			'response' => $response,
			'events'   => $events,
		];
	}

	/**
	 * Returns the debug messages from a GraphQL response.
	 *
	 * @return string[]
	 */
	public function get_debug_messages( array $response ): array {
		return array_values( wp_list_pluck( $response['extensions']['debug'] ?? [], 'message' ) );
	}
}
