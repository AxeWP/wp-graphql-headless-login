<?php
/**
 * Tests the Main class.
 *
 * @package WPGraphQL\Login\Tests\Integration
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Tests\Integration;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use WPGraphQL\Login\Admin\Settings\AccessControlSettings;
use WPGraphQL\Login\Admin\Settings\PluginSettings;
use WPGraphQL\Login\Admin\Settings\ProviderSettings;
use WPGraphQL\Login\Admin\Upgrade\AbstractUpgrade;
use WPGraphQL\Login\Admin\Upgrade\UpgradeRegistry;
use WPGraphQL\Login\Main;
use WPGraphQL\Login\Tests\TestCase;
use WPGraphQL\Login\Utils\Utils;

/**
 * Tests the Main class.
 */
#[CoversClass( Main::class )]
#[CoversClass( UpgradeRegistry::class )]
#[CoversFunction( 'WPGraphQL\\Login\\activation_callback' )]
#[CoversFunction( 'WPGraphQL\\Login\\deactivation_callback' )]
#[CoversFunction( 'WPGraphQL\\Login\\delete_data' )]
class MainTest extends TestCase {
	/**
	 * {@inheritDoc}
	 */
	protected function tearDown(): void {
		$this->reset_utils_properties();

		parent::tearDown();
	}

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

	/**
	 * Tests that activating the plugin fires the activation action and runs the pending upgrades.
	 */
	public function test_on_activation_runs_upgrades(): void {
		delete_option( AbstractUpgrade::VERSION_OPTION_KEY );
		$activations = did_action( 'graphql_login_activate' );

		Main::get_instance()->on_activation();

		$this->assertSame( $activations + 1, did_action( 'graphql_login_activate' ) );
		$this->assertSame( WPGRAPHQL_LOGIN_VERSION, get_option( AbstractUpgrade::VERSION_OPTION_KEY ) );
	}

	/**
	 * Tests that deactivating the plugin keeps its data by default.
	 */
	public function test_on_deactivation_keeps_data_by_default(): void {
		update_option( AbstractUpgrade::VERSION_OPTION_KEY, WPGRAPHQL_LOGIN_VERSION );
		update_option( AccessControlSettings::get_slug(), [ 'shouldBlockUnauthorizedDomains' => true ] );
		$deactivations = did_action( 'graphql_login_deactivate' );
		$deletions     = did_action( 'graphql_login_delete_data' );

		Main::get_instance()->on_deactivation();

		$this->assertSame( $deactivations + 1, did_action( 'graphql_login_deactivate' ) );
		$this->assertSame( $deletions, did_action( 'graphql_login_delete_data' ) );
		$this->assertSame( WPGRAPHQL_LOGIN_VERSION, get_option( AbstractUpgrade::VERSION_OPTION_KEY ) );
		$this->assertSame( [ 'shouldBlockUnauthorizedDomains' => true ], get_option( AccessControlSettings::get_slug() ) );
	}

	/**
	 * Tests that deactivating the plugin deletes its settings and client configurations when `delete_data_on_deactivate` is enabled.
	 */
	public function test_on_deactivation_deletes_data_when_enabled(): void {
		update_option( AbstractUpgrade::VERSION_OPTION_KEY, WPGRAPHQL_LOGIN_VERSION );
		update_option( AccessControlSettings::get_slug(), [ 'shouldBlockUnauthorizedDomains' => true ] );
		update_option( ProviderSettings::$settings_prefix . 'password', [ 'isEnabled' => true ] );
		Utils::update_plugin_setting( 'delete_data_on_deactivate', true );
		$deletions = did_action( 'graphql_login_delete_data' );

		Main::get_instance()->on_deactivation();

		$this->assertSame( $deletions + 1, did_action( 'graphql_login_delete_data' ) );
		$this->assertFalse( get_option( AbstractUpgrade::VERSION_OPTION_KEY, false ) );
		$this->assertFalse( get_option( AccessControlSettings::get_slug(), false ) );
		$this->assertFalse( get_option( PluginSettings::get_slug(), false ) );
		$this->assertFalse( get_option( ProviderSettings::$settings_prefix . 'password', false ) );
	}
}
