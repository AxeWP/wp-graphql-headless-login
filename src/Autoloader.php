<?php
/**
 * Includes the composer Autoloader used for packages and classes in the src/ directory.
 *
 * @package WPGraphQL\Login
 * @since @todo
 */

namespace WPGraphQL\Login;

/**
 * Class - Autoloader
 */
class Autoloader {
	/**
	 * Attempts to autoload the Composer dependencies.
	 */
	public static function autoload(): bool {
		// If we're not *supposed* to autoload anything, then return true.
		if ( defined( 'WPGRAPHQL_LOGIN_AUTOLOAD' ) && false === WPGRAPHQL_LOGIN_AUTOLOAD ) {
			return true;
		}

		$autoloader = dirname( __DIR__ ) . '/vendor/autoload.php';
		return self::require_autoloader( $autoloader );
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

		return (bool) require_once $autoloader_file; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable
	}

	/**
	 * Displays a notice if the autoloader is missing.
	 */
	protected static function missing_autoloader_notice(): void {
		$error_message = __( 'Headless Login for WPGraphQL: The Composer autoloader was not found. If you installed the plugin from the GitHub source, make sure to run `composer install`.', 'wp-graphql-headless-login' );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( esc_html( $error_message ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}

		add_action(
			'admin_notices',
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
