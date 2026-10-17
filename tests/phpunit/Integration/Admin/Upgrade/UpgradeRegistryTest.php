<?php
/**
 * Tests the upgrade routines.
 *
 * @package WPGraphQL\Login\Tests\Integration\Admin\Upgrade
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Tests\Integration\Admin\Upgrade;

use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use WPGraphQL\Login\Admin\Upgrade\AbstractUpgrade;
use WPGraphQL\Login\Admin\Upgrade\UpgradeRegistry;
use WPGraphQL\Login\Tests\TestCase;

/**
 * An upgrade that succeeds.
 */
class MockUpgrade extends AbstractUpgrade {
	/**
	 * The version this upgrade applies to.
	 */
	public static string $version = '0.0.2';

	/**
	 * {@inheritDoc}
	 */
	protected function upgrade(): void {
		update_option( 'WP_GRAPHQL_LOGIN_MOCK_UPGRADE', true );
	}
}

/**
 * An upgrade that throws.
 */
class MockFailedUpgrade extends AbstractUpgrade {
	/**
	 * The version this upgrade applies to.
	 */
	public static string $version = '99.99.99';

	/**
	 * {@inheritDoc}
	 *
	 * @throws \Exception Always.
	 */
	protected function upgrade(): void {
		throw new Exception( 'Upgrade failed.' );
	}
}

/**
 * An upgrade older than the stored version, so it is skipped.
 */
class MockSkippedUpgrade extends AbstractUpgrade {
	/**
	 * The version this upgrade applies to.
	 */
	public static string $version = '0.0.1';

	/**
	 * {@inheritDoc}
	 */
	protected function upgrade(): void {
		update_option( 'WP_GRAPHQL_LOGIN_MOCK_SKIPPED_UPGRADE', true );
	}
}

/**
 * A registry of the successful and skipped mock upgrades.
 */
class MockUpgradeRegistry extends UpgradeRegistry {
	/**
	 * {@inheritDoc}
	 */
	protected static function get_upgrade_classes(): array {
		return [
			MockUpgrade::class,
			MockSkippedUpgrade::class,
		];
	}
}

/**
 * A registry that also includes the failing mock upgrade.
 */
class MockFailedUpgradeRegistry extends UpgradeRegistry {
	/**
	 * {@inheritDoc}
	 */
	protected static function get_upgrade_classes(): array {
		return [
			MockSkippedUpgrade::class, // This upgrade should be skipped.
			MockFailedUpgrade::class, // This upgrade should fail.
			MockUpgrade::class, // This upgrade should not run.
		];
	}
}

/**
 * Tests the UpgradeRegistry and AbstractUpgrade lifecycle.
 */
#[CoversClass( UpgradeRegistry::class )]
#[CoversClass( AbstractUpgrade::class )]
class UpgradeRegistryTest extends TestCase {
	/**
	 * {@inheritDoc}
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->cleanup_upgrade_state();
	}

	/**
	 * Tests that run() applies the upgrade and stores its version, but only once.
	 */
	public function test_run_applies_upgrade_and_updates_version(): void {
		// Test with no version set.
		$upgrade = new MockUpgrade();
		$success = $upgrade->run();

		$this->assertTrue( $success, 'The upgrade process should run successfully.' );
		$this->assertTrue( get_option( 'WP_GRAPHQL_LOGIN_MOCK_UPGRADE' ), 'The AbstractUpgrade::upgrade() method should run if the version is not set.' );
		$this->assertFalse( get_transient( AbstractUpgrade::ERROR_TRANSIENT_KEY ), 'The error transient should not be set if the upgrade is successful.' );
		$this->assertEquals( '0.0.2', get_option( AbstractUpgrade::VERSION_OPTION_KEY ), 'The version should be updated if the upgrade is successful.' );

		// Test with a version set.
		update_option( AbstractUpgrade::VERSION_OPTION_KEY, '0.0.1' );

		$upgrade = new MockUpgrade();
		$success = $upgrade->run();

		$this->assertTrue( $success, 'The upgrade process should run successfully.' );
		$this->assertTrue( get_option( 'WP_GRAPHQL_LOGIN_MOCK_UPGRADE' ), 'The AbstractUpgrade::upgrade() method should run if the version is set.' );
		$this->assertFalse( get_transient( AbstractUpgrade::ERROR_TRANSIENT_KEY ), 'The error transient should not be set if the upgrade is successful.' );
		$this->assertEquals( '0.0.2', get_option( AbstractUpgrade::VERSION_OPTION_KEY ), 'The version should be updated if the upgrade is successful.' );

		// Running the upgrade again should not run the upgrade method.
		$upgrade = new MockUpgrade();
		$success = $upgrade->run();

		$this->assertTrue( $success, 'The upgrade process should run successfully.' );
		$this->assertTrue( get_option( 'WP_GRAPHQL_LOGIN_MOCK_UPGRADE' ), 'The AbstractUpgrade::upgrade() method should not run if the version is not set.' );
		$this->assertFalse( get_transient( AbstractUpgrade::ERROR_TRANSIENT_KEY ), 'The error transient should not be set if the upgrade is successful.' );
		$this->assertEquals( '0.0.2', get_option( AbstractUpgrade::VERSION_OPTION_KEY ), 'The version should not be updated if the upgrade is successful.' );
	}

	/**
	 * Tests that a failed upgrade stores an error that is shown as an admin notice until an upgrade succeeds.
	 */
	public function test_run_failure_shows_error_notice_until_next_success(): void {
		update_option( AbstractUpgrade::VERSION_OPTION_KEY, '0.0.1' );

		$expected = [
			'message' => 'Upgrade failed.',
			'version' => '99.99.99',
		];

		$upgrade = new MockFailedUpgrade();
		$success = $upgrade->run();

		$this->assertFalse( $success );

		$actual = get_transient( AbstractUpgrade::ERROR_TRANSIENT_KEY );

		$this->assertIsArray( $actual );
		$this->assertEquals( $expected, $actual );

		// Test that the error message is output on the admin_notices hook.
		$this->assertStringContainsString( 'Upgrade failed.', $this->get_failed_upgrade_notice() );

		// Test that the error message is cleared.
		$upgrade = new MockUpgrade();
		$success = $upgrade->run();

		$this->assertTrue( $success );

		$actual = get_transient( AbstractUpgrade::ERROR_TRANSIENT_KEY );

		$this->assertFalse( $actual );

		// Failed upgrade notice should not be displayed.
		$this->assertSame( '', $this->get_failed_upgrade_notice() );
	}

	/**
	 * Tests that run() skips upgrades that aren't newer than the stored version.
	 */
	public function test_run_skips_upgrade_older_than_stored_version(): void {
		update_option( AbstractUpgrade::VERSION_OPTION_KEY, '0.0.2' );

		$upgrade = new MockSkippedUpgrade();
		$success = $upgrade->run();

		$this->assertTrue( $success );
		$this->assertFalse( get_option( 'WP_GRAPHQL_LOGIN_MOCK_SKIPPED_UPGRADE' ) );
	}

	/**
	 * Tests that do_upgrades() runs the pending upgrades and stores the plugin version.
	 */
	public function test_do_upgrades_runs_pending_upgrades(): void {
		// With no version set, the newer upgrade runs first and the older one is then skipped.
		MockUpgradeRegistry::do_upgrades();

		$this->assertTrue( get_option( 'WP_GRAPHQL_LOGIN_MOCK_UPGRADE' ) );
		$this->assertFalse( get_option( 'WP_GRAPHQL_LOGIN_MOCK_SKIPPED_UPGRADE' ) );
		$this->assertFalse( get_transient( AbstractUpgrade::ERROR_TRANSIENT_KEY ) );

		// Test with a version set.
		$this->cleanup_upgrade_state();
		update_option( AbstractUpgrade::VERSION_OPTION_KEY, '0.0.1' );

		MockUpgradeRegistry::do_upgrades();

		$this->assertTrue( get_option( 'WP_GRAPHQL_LOGIN_MOCK_UPGRADE' ) );
		$this->assertFalse( get_option( 'WP_GRAPHQL_LOGIN_MOCK_SKIPPED_UPGRADE' ) );
		$this->assertEquals( WPGRAPHQL_LOGIN_VERSION, get_option( AbstractUpgrade::VERSION_OPTION_KEY ), 'The version should be updated to the plugin version if the upgrade is successful.' );
	}

	/**
	 * Tests that do_upgrades() halts at a failed upgrade without running later upgrades or bumping the version.
	 */
	public function test_do_upgrades_halts_on_failed_upgrade(): void {
		update_option( AbstractUpgrade::VERSION_OPTION_KEY, '0.0.1' );

		MockFailedUpgradeRegistry::do_upgrades();

		$this->assertFalse( get_option( 'WP_GRAPHQL_LOGIN_MOCK_SKIPPED_UPGRADE' ), 'Upgrades older than the stored version should be skipped.' );
		$this->assertFalse( get_option( 'WP_GRAPHQL_LOGIN_MOCK_UPGRADE' ), 'Upgrades after the failed one should not run.' );
		$this->assertSame( '0.0.1', get_option( AbstractUpgrade::VERSION_OPTION_KEY ), 'The version should not be updated if an upgrade fails.' );
		$this->assertSame(
			[
				'version' => '99.99.99',
				'message' => 'Upgrade failed.',
			],
			get_transient( AbstractUpgrade::ERROR_TRANSIENT_KEY )
		);
	}

	/**
	 * Returns the output of the failed upgrade admin notice.
	 */
	private function get_failed_upgrade_notice(): string {
		ob_start();
		UpgradeRegistry::failed_upgrade_notice();

		return (string) ob_get_clean();
	}

	/**
	 * Cleans up the options and transients used during the upgrade process.
	 */
	private function cleanup_upgrade_state(): void {
		delete_option( AbstractUpgrade::VERSION_OPTION_KEY );
		delete_option( 'WP_GRAPHQL_LOGIN_MOCK_UPGRADE' );
		delete_option( 'WP_GRAPHQL_LOGIN_MOCK_SKIPPED_UPGRADE' );
		delete_transient( AbstractUpgrade::ERROR_TRANSIENT_KEY );
	}
}
