<?php
/**
 * Tests the Main class.
 *
 * @package WPGraphQL\Login\Tests\Integration
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Tests\Integration;

use PHPUnit\Framework\Attributes\CoversClass;
use WPGraphQL\Login\Main;
use WPGraphQL\Login\Tests\TestCase;

/**
 * Tests the Main class.
 */
#[CoversClass( Main::class )]
class MainTest extends TestCase {
	/**
	 * Tests that get_instance() passes the plugin instance to the `graphql_login_init` action.
	 */
	public function test_get_instance_fires_init_action(): void {
		$received = null;
		add_action(
			'graphql_login_init',
			static function ( Main $instance ) use ( &$received ): void {
				$received = $instance;
			}
		);

		$instance = Main::get_instance();

		$this->assertSame( $instance, $received );
	}
}
