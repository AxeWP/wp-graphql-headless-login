<?php
/**
 * Shared helpers for the plugin tests.
 *
 * @package Tests\WPGraphQL\Login\Traits
 */

namespace Tests\WPGraphQL\Login\Traits;

use ReflectionClass;
use WPGraphQL\Login\Admin\Settings\PluginSettings;
use WPGraphQL\Login\Admin\Settings\ProviderSettings;
use WPGraphQL\Login\Auth\TokenManager;

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
		$reflection = new ReflectionClass( 'WPGraphQL\Login\TypeRegistry' );
		$property   = $reflection->getProperty( 'registry' );
		$property->setAccessible( true );
		$property->setValue( null, [] );
	}

	/**
	 * Registers the given provider config class on the ProviderRegistry.
	 */
	public function mock_provider_config( string $provider_class ): void {
		$reflection = new ReflectionClass( 'WPGraphQL\Login\Auth\ProviderRegistry' );
		$property   = $reflection->getProperty( 'providers' );
		$property->setAccessible( true );
		$providers = $property->getValue();

		$mocked_provider                           = new $provider_class();
		$providers[ $mocked_provider->get_slug() ] = $mocked_provider;
		$property->setValue( null, $providers );
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
		update_option( PluginSettings::get_slug() . 'jwt_secret_key', $site_secret );
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
}
