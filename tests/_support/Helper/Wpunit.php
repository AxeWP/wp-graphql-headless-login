<?php
namespace Tests\WPGraphQL\Login\Helper;

use ReflectionClass;
use WPGraphQL\Login\Admin\Settings\PluginSettings;
use WPGraphQL\Login\Admin\Settings\ProviderSettings;
use WPGraphQL\Login\Auth\TokenManager;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Wpunit extends \Codeception\Module {
	public function mock_provider_config( string $provider_class ) {
		$reflection = new ReflectionClass( 'WPGraphQL\Login\Auth\ProviderRegistry' );

		$property = $reflection->getProperty( 'providers' );
		$property->setAccessible( true );
		$providers = $property->getValue();

		$mocked_provider = new $provider_class();

		$providers[ $mocked_provider->get_slug() ] = $mocked_provider;
		$property->setValue( $providers );
	}

	public function set_client_config( string $slug, array $config ) {
		update_option( ProviderSettings::$settings_prefix . $slug, $config );

		/** @var \Tests\WPGraphQL\Login\Helper\Helper $helper */
		$helper = $this->getModule( '\Tests\WPGraphQL\Login\Helper\Helper' );
		$helper->reset_utils_properties();
		$helper->reset_provider_registry();
	}

	public function clear_client_config( string $slug ) {
		update_option( ProviderSettings::$settings_prefix . $slug, [] );

		/** @var \Tests\WPGraphQL\Login\Helper\Helper $helper */
		$helper = $this->getModule( '\Tests\WPGraphQL\Login\Helper\Helper' );
		$helper->reset_utils_properties();
		$helper->reset_provider_registry();
	}

	public function generate_user_tokens( string $user_id ): array {
		/** @var \Tests\WPGraphQL\Login\Helper\Helper $helper */
		$helper = $this->getModule( '\Tests\WPGraphQL\Login\Helper\Helper' );

		$original_user = get_current_user_id();

		wp_set_current_user( $user_id );

		$site_secret = wp_generate_password( 64, false, false );

		update_option( PluginSettings::$settings_prefix . 'jwt_secret_key', $site_secret );
		TokenManager::issue_new_user_secret( $user_id, false );
		$helper->reset_utils_properties();

		$auth_token = TokenManager::get_auth_token( wp_get_current_user(), false );
		$helper->reset_utils_properties();

		$refresh_token = TokenManager::get_refresh_token( wp_get_current_user(), false );
		$helper->reset_utils_properties();

		wp_set_current_user( $original_user );

		return [
			'site_secret'   => $site_secret,
			'auth_token'    => $auth_token,
			'refresh_token' => $refresh_token,
		];
	}
}
