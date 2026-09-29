<?php
/**
 * Tests the plugin dependency checks.
 *
 * @package WPGraphQL\Login\Tests\Integration
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Tests\Integration;

use PHPUnit\Framework\Attributes\CoversClass;
use WPGraphQL\Login\Dependencies;
use WPGraphQL\Login\Tests\TestCase;

/**
 * Tests the Dependencies class.
 */
#[CoversClass( Dependencies::class )]
class DependenciesTest extends TestCase {
	/**
	 * {@inheritDoc}
	 */
	protected function setUp(): void {
		parent::setUp();

		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		// Stand in for the conflicting plugin's main class. PHP < 8.3 can't alias internal classes like stdClass.
		if ( ! class_exists( 'WP_GraphQL_CORS' ) ) {
			$stub = new class() {
			};
			class_alias( $stub::class, 'WP_GraphQL_CORS' );
		}
	}

	/**
	 * Tests that the check passes without admin notices when the dependencies are met and no conflicting plugins are active.
	 */
	public function test_check_passes_without_conflicting_plugins(): void {
		// The conflicting plugin is installed but inactive.
		update_option( 'active_plugins', [ 'wp-graphql/wp-graphql.php' ] );

		$this->assertTrue( Dependencies::check() );
		$this->assertStringNotContainsString( 'Headless Login for WPGraphQL', $this->render_notices( 'admin_notices' ) );
	}

	/**
	 * Tests that an active conflicting plugin fails the check and displays an admin notice.
	 */
	public function test_check_fails_with_conflicting_plugin(): void {
		update_option( 'active_plugins', [ 'wp-graphql/wp-graphql.php', 'wp-graphql-cors/wp-graphql-cors.php' ] );

		$this->assertFalse( Dependencies::check() );

		$expected = 'The plugin &quot;WPGraphQL CORS&quot; is known to conflict with Headless Login for WPGraphQL.';

		$this->assertStringContainsString( $expected, $this->render_notices( 'admin_notices' ) );
		$this->assertStringContainsString( $expected, $this->render_notices( 'network_admin_notices' ) );
	}

	/**
	 * Renders the admin notices for the given hook.
	 *
	 * @param string $hook The admin notices hook.
	 */
	private function render_notices( string $hook ): string {
		ob_start();
		do_action( $hook );

		return (string) ob_get_clean();
	}
}
