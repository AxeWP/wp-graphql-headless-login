<?php


/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause()
 *
 * @SuppressWarnings(PHPMD)
*/
class FunctionalTester extends \Codeception\Actor
{
    use _generated\FunctionalTesterActions;

    /**
     * Define custom actions here
     */
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
}
