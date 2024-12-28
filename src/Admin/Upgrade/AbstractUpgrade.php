<?php
/**
 * The Abstract Upgrade class.
 *
 * Handles the upgrade process for the plugin.
 *
 * @package WPGraphQL\Login\Admin\Upgrade
 * @since 0.4.0
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Admin\Upgrade;

/**
 * Class AbstractUpgrade
 */
abstract class AbstractUpgrade {
	/**
	 * The transient key for the error.
	 */
	public const ERROR_TRANSIENT_KEY = 'wpgraphql_login_upgrade_error';

	/**
	 * The key to store the version.
	 */
	public const VERSION_OPTION_KEY = 'wp_graphql_login_version';

	/**
	 * The version to upgrade to.
	 *
	 * If a plugin is below this version, the upgrade will run.
	 *
	 * @var string
	 */
	public static string $version;

	/**
	 * Run the upgrade.
	 */
	public function run(): bool {
		$current_version = get_option( self::VERSION_OPTION_KEY, null );

		// If the current version is the same as the version to upgrade to, there is nothing to upgrade.
		if ( ! empty( $current_version ) && version_compare( static::$version, $current_version, '<=' ) ) {
			return true;
		}

		try {
			$this->upgrade();
		} catch ( \Throwable $e ) {
			// Log the error.
			$this->set_error( $e->getMessage() );

			// Return false to indicate the upgrade failed.
			return false;
		}

		// If the upgrade was successful, clear the error.
		$this->clear_error();

		// Update the version.
		update_option( self::VERSION_OPTION_KEY, static::$version );

		return true;
	}

	/**
	 * The upgrade process.
	 *
	 * @throws \Exception Throws an exception if the upgrade fails.
	 */
	abstract protected function upgrade(): void;

	/**
	 * Sets the error transient.
	 *
	 * @param string $message The error message.
	 */
	protected function set_error( string $message ): void {
		set_transient(
			self::ERROR_TRANSIENT_KEY,
			[
				'version' => static::$version,
				'message' => $message,
			],
			0
		);
	}

	/**
	 * Clears the error transient.
	 */
	protected function clear_error(): void {
		delete_transient( self::ERROR_TRANSIENT_KEY );
	}
}
