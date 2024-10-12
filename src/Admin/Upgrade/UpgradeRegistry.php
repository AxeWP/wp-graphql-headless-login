<?php
/**
 * The Upgrade Registry
 *
 * @package WPGraphQL\Login\Admin\Upgrade
 * @since @next-version
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Admin\Upgrade;

use WPGraphQL\Login\Vendor\AxeWP\GraphQL\Interfaces\Registrable;

/**
 * Class UpgradeRegistry
 */
class UpgradeRegistry implements Registrable {
	/**
	 * {@inheritDoc}
	 */
	public static function init(): void {
		// Register the upgrade process - in case the activation hook is missed.
		add_action( 'admin_init', [ self::class, 'do_upgrades' ] );

		// Register error notices.
		add_action( 'admin_notices', [ self::class, 'failed_upgrade_notice' ] );
	}

	/**
	 * Run the upgrade process.
	 */
	public static function do_upgrades(): void {
		$upgrade_classes = static::get_upgrade_classes();

		// Remove all the unneeded upgrade classes.
		$db_version = get_option( AbstractUpgrade::VERSION_OPTION_KEY, null );

		if ( empty( $db_version ) ) {
			return;
		}

		foreach ( $upgrade_classes as $upgrade_class ) {
			if ( version_compare( $upgrade_class::$version, $db_version, '<=' ) ) {
				unset( $upgrade_classes[ array_search( $upgrade_class, $upgrade_classes, true ) ] );
			}
		}

		// Run the individual upgrades.
		foreach ( $upgrade_classes as $upgrade_class ) {
			$instance = new $upgrade_class();
			$success  = $instance->run();

			// If the upgrade fails, stop the upgrade process.
			if ( ! $success ) {
				return;
			}
		}

		// Update the version in the database.
		update_option( AbstractUpgrade::VERSION_OPTION_KEY, WPGRAPHQL_LOGIN_VERSION );
	}

	/**
	 * Display a notice if the upgrade fails.
	 */
	public static function failed_upgrade_notice(): void {
		$error = get_transient( AbstractUpgrade::ERROR_TRANSIENT_KEY );

		if ( empty( $error ) || ! isset( $error['message'] ) || ! isset( $error['version'] ) ) {
			return;
		}

		// Display the error message.
		?>
		<div class="notice notice-error">
			<p>
				<?php
				printf(
					// translators: %1$s is the version the plugin was trying to upgrade to, %2$s is the version the plugin halted at, %3$s is the error message.
					esc_html__( 'An error occured while upgrading to version %1$s. Te upgrade process has been halted at version %2$s. Error message: %3$s', 'wp-graphql-headless-login' ),
					esc_html( WPGRAPHQL_LOGIN_VERSION ),
					esc_html( $error['version'] ),
					esc_html( $error['message'] )
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * The upgrade classes.
	 *
	 * @return class-string<\WPGraphQL\Login\Admin\Upgrade\AbstractUpgrade>[]
	 */
	protected static function get_upgrade_classes(): array {
		return [
			V0_4_0::class,
		];
	}
}
