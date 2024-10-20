<?php
namespace Tests\WPGraphQL\Login\Helper;

use ReflectionClass;

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
}
