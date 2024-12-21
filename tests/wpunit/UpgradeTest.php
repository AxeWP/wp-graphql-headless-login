<?php

use Codeception\TestCase\WPTestCase;
use WPGraphQL\Login\Admin\Settings\AccessControlSettings;
use WPGraphQL\Login\Admin\Settings\CookieSettings;
use WPGraphQL\Login\Admin\Settings\PluginSettings;
use WPGraphQL\Login\Admin\Upgrade\AbstractUpgrade;
use WPGraphQL\Login\Admin\Upgrade\UpgradeRegistry;

class MockUpgrade extends AbstractUpgrade {
	public static string $version = '0.0.2';

	public function upgrade(): void {
		// Do nothing.
		update_option( 'WP_GRAPHQL_LOGIN_MOCK_UPGRADE', true );
	}
}

class MockFailedUpgrade extends AbstractUpgrade {
	public static string $version = '99.99.99';

	public function upgrade(): void {
		throw new \Exception( 'Upgrade failed.' );
	}
}

class MockedSkippedUpgrade extends AbstractUpgrade {
	public static string $version = '0.0.1';

	public function upgrade(): void {
		update_option( 'WP_GRAPHQL_LOGIN_MOCK_SKIPPED_UPGRADE', true );
	}
}

class MockUpgradeRegistry extends UpgradeRegistry {
	public static function get_upgrade_classes(): array {
		return [
			MockUpgrade::class,
			MockedSkippedUpgrade::class,
		];
	}
}

class MockFailedUpgradeRegistry extends UpgradeRegistry {
	public static function get_upgrade_classes(): array {
		return [
			MockedSkippedUpgrade::class, // This upgrade should be skipped.
			MockFailedUpgrade::class, // This upgrade should fail.
			MockUpgrade::class, // This upgrade should not run.
		];
	}
}

/**
 * Tests the UpgradeRegistry and AbstractUpgrade lifecycle.
 */
class UpgradeTest extends WPTestCase {
	protected function setUp(): void {
		parent::setUp();

		$this->cleanup_upgrade_state();
	}

	protected function tearDown(): void {
		$this->cleanup_upgrade_state();

		parent::tearDown();
	}

	/**
	 * Test that the upgrade process runs successfully.
	 */
	public function testUpgradeSuccess(): void {
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
	 * Test that the upgrade process fails.
	 */
	public function testUpgradeFailure(): void {
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
		$this->expectOutputRegex( '/Upgrade failed./' );
		UpgradeRegistry::failed_upgrade_notice();

		// Test that the error message is cleared.
		$upgrade = new MockUpgrade();
		$success = $upgrade->run();

		$this->assertTrue( $success );

		$actual = get_transient( AbstractUpgrade::ERROR_TRANSIENT_KEY );

		$this->assertFalse( $actual );

		// Failed upgrade notice should not be displayed.
		$this->expectOutputString( '' );
		UpgradeRegistry::failed_upgrade_notice();

		// Cleanup.
		delete_transient( AbstractUpgrade::ERROR_TRANSIENT_KEY );
	}

	/**
	 * Test that the upgrade process skips upgrades that are not needed.
	 */
	public function testUpgradeSkipped(): void {
		update_option( AbstractUpgrade::VERSION_OPTION_KEY, '0.0.2' );

		$upgrade = new MockedSkippedUpgrade();
		$success = $upgrade->run();

		$this->assertTrue( $success );
		$this->assertFalse( get_option( 'WP_GRAPHQL_LOGIN_MOCK_SKIPPED_UPGRADE' ) );
	}

	/**
	 * Test that the upgrade process runs all upgrades.
	 */
	public function testDoUpgrades(): void {
		// If the version is not set, no upgrades should run.
		MockUpgradeRegistry::do_upgrades();

		$this->assertTrue( get_option( 'WP_GRAPHQL_LOGIN_MOCK_UPGRADE' ) );
		$this->assertFalse( get_option( 'WP_GRAPHQL_LOGIN_MOCK_SKIPPED_UPGRADE' ) );
		$this->assertFalse( get_transient( AbstractUpgrade::ERROR_TRANSIENT_KEY ) );

		// Test with a version set.
		$this->cleanup_upgrade_state();
		$success = update_option( AbstractUpgrade::VERSION_OPTION_KEY, '0.0.1' );

		MockUpgradeRegistry::do_upgrades();

		$this->assertTrue( get_option( 'WP_GRAPHQL_LOGIN_MOCK_UPGRADE' ) );
		$this->assertFalse( get_option( 'WP_GRAPHQL_LOGIN_MOCK_SKIPPED_UPGRADE' ) );
		$this->assertEquals( WPGRAPHQL_LOGIN_VERSION, get_option( AbstractUpgrade::VERSION_OPTION_KEY ), 'The version should be updated to the plugin version if the upgrade is successful.' );
	}

	/**
	 * Tests the v0.4.0 upgrade process.
	 */
	public function testV0_4_0Upgrade(): void {
		global $wpdb;
		// Set the old settings.
		update_option( 'wp_graphql_login_settings_show_advanced_settings', true );
		update_option( 'wp_graphql_login_settings_delete_data_on_deactivate', true );
		update_option( 'wp_graphql_login_settings_jwt_secret_key', 'secret' );

		$status = $wpdb->insert(
			$wpdb->options,
			[
				'option_name'  => 'wpgraphql_login_access_control',
				'option_value' => serialize( [ 'hasAccessControlAllowCredentials' => true ] ),
			]
		);

		// Set the version to < 0.4.0.
		update_option( AbstractUpgrade::VERSION_OPTION_KEY, '0.3.0' );

		$upgrade = new \WPGraphQL\Login\Admin\Upgrade\V0_4_0();
		$upgrade->run();

		// Check the new settings.
		$this->assertEquals(
			[
				'show_advanced_settings'    => true,
				'delete_data_on_deactivate' => true,
				'jwt_secret_key'            => 'secret',
			],
			get_option( PluginSettings::get_slug() )
		);
		$this->assertTrue(
			get_option( CookieSettings::get_slug() )['hasAccessControlAllowCredentials']
		);

		// Check the old settings.
		$this->assertFalse( get_option( 'wp_graphql_login_settings_show_advanced_settings' ) );
		$this->assertFalse( get_option( 'wp_graphql_login_settings_delete_data_on_deactivate' ) );
		$this->assertFalse( get_option( 'wp_graphql_login_settings_jwt_secret_key' ) );
		$this->assertArrayNotHasKey( 'hasAccessControlAllowCredentials', get_option( AccessControlSettings::get_slug(), [] ) );
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
