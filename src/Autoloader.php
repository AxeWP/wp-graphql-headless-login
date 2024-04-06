<?php
/**
 * Includes the composer Autoloader used for packages and classes in the src/ directory.
 *
 * @package WPGraphQL\Login
 * @since 0.1.4
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login;

/**
 * Class - Autoloader
 *
 * @internal
 */
class Autoloader {
	/**
	 * Whether the autoloader has been loaded.
	 *
	 * @var bool
	 */
	protected static bool $is_loaded = false;

	/**
	 * Attempts to autoload the Composer dependencies.
	 */
	public static function autoload(): bool {
		// If we're not *supposed* to autoload anything, then return true.
		if ( defined( 'WPGRAPHQL_LOGIN_AUTOLOAD' ) && false === WPGRAPHQL_LOGIN_AUTOLOAD ) {
			return true;
		}

		if ( self::$is_loaded ) {
			return self::$is_loaded;
		}

		$autoloader      = dirname( __DIR__ ) . '/vendor/autoload.php';
		self::$is_loaded = self::require_autoloader( $autoloader );

		return self::$is_loaded;
	}

	/**
	 * Attempts to load the autoloader file, if it exists.
	 *
	 * @param string $autoloader_file The path to the autoloader file.
	 */
	protected static function require_autoloader( string $autoloader_file ): bool {
		if ( ! is_readable( $autoloader_file ) ) {
				self::missing_autoloader_notice();
				return false;
		}

		return (bool) require_once $autoloader_file; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable -- Autoloader is a Composer file.
	}

	/**
	 * Displays a notice if the autoloader is missing.
	 */
	protected static function missing_autoloader_notice(): void {
		$error_message = __( 'Headless Login for WPGraphQL: The Composer autoloader was not found. If you installed the plugin from the GitHub source, make sure to run `composer install`.', 'wp-graphql-headless-login' );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( esc_html( $error_message ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- This is a development notice.
		}

		$hooks = [
			'admin_notices',
			'network_admin_notices',
		];

		foreach ( $hooks as $hook ) {
			add_action(
				$hook,
				static function () use ( $error_message ) {
					?>
					<div class="error notice">
						<p>
							<?php echo esc_html( $error_message ); ?>
						</p>
					</div>
					<?php
				}
			);
		}
	}
}
