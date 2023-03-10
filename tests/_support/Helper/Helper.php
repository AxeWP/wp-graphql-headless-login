<?php
namespace Helper;

use ReflectionClass;
use WPGraphQL\Login\Admin\Settings\ProviderSettings;

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
}
