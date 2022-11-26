<?php
namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Functional extends \Codeception\Module {

	public function reset_utils_properties() {
		$reflection = new ReflectionClass( 'WPGraphQL\Login\Utils\Utils' );
		// Reset Providers.
		$property = $reflection->getProperty( 'providers' );
		$property->setAccessible( true );
		$property->setValue( [] );

		// Reset Settings.
		$property = $reflection->getProperty( 'settings' );
		$property->setAccessible( true );
		$property->setValue( [] );
	}
}
