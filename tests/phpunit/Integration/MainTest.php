<?php
/**
 * Tests the Main class.
 *
 * @package Tests\WPGraphQL\Login\Integration
 */

namespace Tests\WPGraphQL\Login\Integration;

use Tests\WPGraphQL\Login\TestCase;
use WPGraphQL\Login\Main;

/**
 * Tests Main.
 */
class MainTest extends TestCase {
	/**
	 * Tests get_instance() passes the plugin instance to the `graphql_login_init` action.
	 */
	public function testGetInstanceFiresInitAction() {
		$received = null;
		add_action(
			'graphql_login_init',
			static function ( $instance ) use ( &$received ) {
				$received = $instance;
			}
		);

		$instance = Main::get_instance();

		$this->assertSame( $instance, $received );
	}
}
