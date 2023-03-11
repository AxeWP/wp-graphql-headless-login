<?php
namespace Helper;

use ReflectionClass;
use WPGraphQL\Login\Admin\Settings\PluginSettings;
use WPGraphQL\Login\Admin\Settings\ProviderSettings;
use WPGraphQL\Login\Auth\TokenManager;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Helper extends \Codeception\Module {

	public function reset_utils_properties() {
		$reflection = new ReflectionClass( 'WPGraphQL\Login\Utils\Utils' );
		// Reset Providers
		$property = $reflection->getProperty( 'providers' );
		$property->setAccessible( true );
		$property->setValue( [] );

		// Reset Settings
		$property = $reflection->getProperty( 'settings' );
		$property->setAccessible( true );
		$property->setValue( [] );

		// Reset access_ontrol
		$property = $reflection->getProperty( 'access_control' );
		$property->setAccessible( true );
		$property->setValue( null );
	}

	public function reset_provider_registry() {
		$reflection = new ReflectionClass( 'WPGraphQL\Login\Auth\ProviderRegistry' );
		// Reset Providers
		$property = $reflection->getProperty( 'instance' );
		$property->setAccessible( true );
		$property->setValue( null );
	}

	public function reset_type_registry() {
		$reflection = new ReflectionClass( 'WPGraphQL\Login\TypeRegistry' );
		// Reset Registry.
		$property = $reflection->getProperty( 'registry' );
		$property->setAccessible( true );
		$property->setValue( [] );
	}

	public function set_client_config( string $slug, array $config ) {
		update_option( ProviderSettings::$settings_prefix . $slug, $config );
		$this->reset_utils_properties();
		$this->reset_provider_registry();
	}

	public function clear_client_config( string $slug ) {
		update_option( ProviderSettings::$settings_prefix . $slug, [] );
		$this->reset_utils_properties();
		$this->reset_provider_registry();
	}

	public function generate_user_tokens( string $user_id ) : array {
		$original_user = get_current_user_id();

		wp_set_current_user( $user_id );

		$site_secret = wp_generate_password( 64, false, false );

		update_option( PluginSettings::$settings_prefix . 'jwt_secret_key', $site_secret );
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
