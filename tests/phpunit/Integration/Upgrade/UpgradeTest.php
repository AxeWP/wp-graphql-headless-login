<?php
/**
 * Tests the upgrade routines.
 *
 * @package Tests\WPGraphQL\Login\Integration\Upgrade
 */

namespace Tests\WPGraphQL\Login\Integration\Upgrade;

use Tests\WPGraphQL\Login\TestCase;
use WPGraphQL\Login\Admin\Settings\AccessControlSettings;
use WPGraphQL\Login\Admin\Settings\CookieSettings;
use WPGraphQL\Login\Admin\Settings\PluginSettings;
use WPGraphQL\Login\Admin\Upgrade\AbstractUpgrade;
use WPGraphQL\Login\Admin\Upgrade\UpgradeRegistry;

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
	public function upgrade(): void {
		\update_option( 'WP_GRAPHQL_LOGIN_MOCK_UPGRADE', true );
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
	public function upgrade(): void {
		throw new \Exception( 'Upgrade failed.' );
	}
}

/**
 * An upgrade older than the stored version, so it is skipped.
 */
class MockedSkippedUpgrade extends AbstractUpgrade {
	/**
	 * The version this upgrade applies to.
	 */
	public static string $version = '0.0.1';

	/**
	 * {@inheritDoc}
	 */
	public function upgrade(): void {
		\update_option( 'WP_GRAPHQL_LOGIN_MOCK_SKIPPED_UPGRADE', true );
	}
}

/**
 * A registry of the successful and skipped mock upgrades.
 */
class MockUpgradeRegistry extends UpgradeRegistry {
	/**
	 * {@inheritDoc}
	 */
	public static function get_upgrade_classes(): array {
		return [
			MockUpgrade::class,
			MockedSkippedUpgrade::class,
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
	public static function get_upgrade_classes(): array {
		return [
			MockedSkippedUpgrade::class,
			MockFailedUpgrade::class,
			MockUpgrade::class,
		];
	}
}

/**
 * Tests the UpgradeRegistry and AbstractUpgrade lifecycle.
 */
class UpgradeTest extends TestCase {
	/**
	 * {@inheritDoc}
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->cleanup_upgrade_state();
	}

	/**
	 * {@inheritDoc}
	 */
	protected function tearDown(): void {
		$this->cleanup_upgrade_state();

		parent::tearDown();
	}

	/**
	 * Test that the upgrade process runs successfully.
	 */
	public function testUpgradeSuccess(): void {
		$upgrade = new MockUpgrade();
		$success = $upgrade->run();

		$this->assertTrue( $success, 'The upgrade process should run successfully.' );
		$this->assertTrue( $this->getOption( 'WP_GRAPHQL_LOGIN_MOCK_UPGRADE' ), 'The AbstractUpgrade::upgrade() method should run if the version is not set.' );
		$this->assertFalse( $this->getTransient( AbstractUpgrade::ERROR_TRANSIENT_KEY ), 'The error transient should not be set if the upgrade is successful.' );
		$this->assertEquals( '0.0.2', $this->getOption( AbstractUpgrade::VERSION_OPTION_KEY ), 'The version should be updated if the upgrade is successful.' );

		$this->updateOption( AbstractUpgrade::VERSION_OPTION_KEY, '0.0.1' );

		$upgrade = new MockUpgrade();
		$success = $upgrade->run();

		$this->assertTrue( $success, 'The upgrade process should run successfully.' );
		$this->assertTrue( $this->getOption( 'WP_GRAPHQL_LOGIN_MOCK_UPGRADE' ), 'The AbstractUpgrade::upgrade() method should run if the version is set.' );
		$this->assertFalse( $this->getTransient( AbstractUpgrade::ERROR_TRANSIENT_KEY ), 'The error transient should not be set if the upgrade is successful.' );
		$this->assertEquals( '0.0.2', $this->getOption( AbstractUpgrade::VERSION_OPTION_KEY ), 'The version should be updated if the upgrade is successful.' );

		$upgrade = new MockUpgrade();
		$success = $upgrade->run();

		$this->assertTrue( $success, 'The upgrade process should run successfully.' );
		$this->assertTrue( $this->getOption( 'WP_GRAPHQL_LOGIN_MOCK_UPGRADE' ), 'The AbstractUpgrade::upgrade() method should not run if the version is not set.' );
		$this->assertFalse( $this->getTransient( AbstractUpgrade::ERROR_TRANSIENT_KEY ), 'The error transient should not be set if the upgrade is successful.' );
		$this->assertEquals( '0.0.2', $this->getOption( AbstractUpgrade::VERSION_OPTION_KEY ), 'The version should not be updated if the upgrade is successful.' );
	}

	/**
	 * Test that the upgrade process fails.
	 */
	public function testUpgradeFailure(): void {
		$this->updateOption( AbstractUpgrade::VERSION_OPTION_KEY, '0.0.1' );

		$expected = [
			'message' => 'Upgrade failed.',
			'version' => '99.99.99',
		];

		$upgrade = new MockFailedUpgrade();
		$success = $upgrade->run();

		$this->assertFalse( $success );

		$actual = $this->getTransient( AbstractUpgrade::ERROR_TRANSIENT_KEY );

		$this->assertIsArray( $actual );
		$this->assertEquals( $expected, $actual );

		$this->expectOutputRegex( '/Upgrade failed./' );
		UpgradeRegistry::failed_upgrade_notice();

		$upgrade = new MockUpgrade();
		$success = $upgrade->run();

		$this->assertTrue( $success );

		$actual = $this->getTransient( AbstractUpgrade::ERROR_TRANSIENT_KEY );

		$this->assertFalse( $actual );

		$this->expectOutputString( '' );
		UpgradeRegistry::failed_upgrade_notice();

		$this->deleteTransient( AbstractUpgrade::ERROR_TRANSIENT_KEY );
	}

	/**
	 * Test that the upgrade process skips upgrades that are not needed.
	 */
	public function testUpgradeSkipped(): void {
		$this->updateOption( AbstractUpgrade::VERSION_OPTION_KEY, '0.0.2' );

		$upgrade = new MockedSkippedUpgrade();
		$success = $upgrade->run();

		$this->assertTrue( $success );
		$this->assertFalse( $this->getOption( 'WP_GRAPHQL_LOGIN_MOCK_SKIPPED_UPGRADE' ) );
	}

	/**
	 * Test that the upgrade process runs all upgrades.
	 */
	public function testDoUpgrades(): void {
		MockUpgradeRegistry::do_upgrades();

		$this->assertTrue( $this->getOption( 'WP_GRAPHQL_LOGIN_MOCK_UPGRADE' ) );
		$this->assertFalse( $this->getOption( 'WP_GRAPHQL_LOGIN_MOCK_SKIPPED_UPGRADE' ) );
		$this->assertFalse( $this->getTransient( AbstractUpgrade::ERROR_TRANSIENT_KEY ) );

		$this->cleanup_upgrade_state();
		$this->updateOption( AbstractUpgrade::VERSION_OPTION_KEY, '0.0.1' );

		MockUpgradeRegistry::do_upgrades();

		$this->assertTrue( $this->getOption( 'WP_GRAPHQL_LOGIN_MOCK_UPGRADE' ) );
		$this->assertFalse( $this->getOption( 'WP_GRAPHQL_LOGIN_MOCK_SKIPPED_UPGRADE' ) );
		$this->assertEquals( WPGRAPHQL_LOGIN_VERSION, $this->getOption( AbstractUpgrade::VERSION_OPTION_KEY ), 'The version should be updated to the plugin version if the upgrade is successful.' );
	}

	/**
	 * Tests the v0.4.0 upgrade process.
	 */
	public function testV0_4_0Upgrade(): void {
		global $wpdb;

		$this->updateOption( 'wp_graphql_login_settings_show_advanced_settings', true );
		$this->updateOption( 'wp_graphql_login_settings_delete_data_on_deactivate', true );
		$this->updateOption( 'wp_graphql_login_settings_jwt_secret_key', 'secret' );

		$wpdb->insert(
			$wpdb->options,
			[
				'option_name'  => 'wpgraphql_login_access_control',
				'option_value' => serialize( [ 'hasAccessControlAllowCredentials' => true ] ),
			]
		);

		$this->updateOption( AbstractUpgrade::VERSION_OPTION_KEY, '0.3.0' );

		$upgrade = new \WPGraphQL\Login\Admin\Upgrade\V0_4_0();
		$upgrade->run();

		$this->assertEquals(
			[
				'show_advanced_settings'    => true,
				'delete_data_on_deactivate' => true,
				'jwt_secret_key'            => 'secret',
			],
			$this->getOption( PluginSettings::get_slug() )
		);
		$this->assertTrue(
			$this->getOption( CookieSettings::get_slug() )['hasAccessControlAllowCredentials']
		);

		$this->assertFalse( $this->getOption( 'wp_graphql_login_settings_show_advanced_settings' ) );
		$this->assertFalse( $this->getOption( 'wp_graphql_login_settings_delete_data_on_deactivate' ) );
		$this->assertFalse( $this->getOption( 'wp_graphql_login_settings_jwt_secret_key' ) );
		$this->assertArrayNotHasKey( 'hasAccessControlAllowCredentials', $this->getOption( AccessControlSettings::get_slug(), [] ) );
	}

	/**
	 * Cleans up the options and transients used during the upgrade process.
	 */
	private function cleanup_upgrade_state(): void {
		$this->deleteOption( AbstractUpgrade::VERSION_OPTION_KEY );
		$this->deleteOption( 'WP_GRAPHQL_LOGIN_MOCK_UPGRADE' );
		$this->deleteOption( 'WP_GRAPHQL_LOGIN_MOCK_SKIPPED_UPGRADE' );
		$this->deleteTransient( AbstractUpgrade::ERROR_TRANSIENT_KEY );
	}

	/**
	 * Wrapper for `get_option()`.
	 */
	private function getOption( string $key, $default = false ) {
		$get_option = '\\get_option';

		return $get_option( $key, $default );
	}

	/**
	 * Wrapper for `update_option()`.
	 */
	private function updateOption( string $key, $value ): void {
		$update_option = '\\update_option';
		$update_option( $key, $value );
	}

	/**
	 * Wrapper for `delete_option()`.
	 */
	private function deleteOption( string $key ): void {
		$delete_option = '\\delete_option';
		$delete_option( $key );
	}

	/**
	 * Wrapper for `get_transient()`.
	 */
	private function getTransient( string $key ) {
		$get_transient = '\\get_transient';

		return $get_transient( $key );
	}

	/**
	 * Wrapper for `delete_transient()`.
	 */
	private function deleteTransient( string $key ): void {
		$delete_transient = '\\delete_transient';
		$delete_transient( $key );
	}
}
