<?php
namespace Helper;

use ReflectionClass;

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
}
