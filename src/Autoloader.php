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
	 * Attempts to load the autoloader, if it exists.
	 *
	 * @return false|mixed Whether the autoloader was loaded.
	 */
	public static function autoload() {
		// If we're not *supposed* to autoload anything, then return true.
		if ( defined( 'WPGRAPHQL_LOGIN_AUTOLOAD' ) && false === WPGRAPHQL_LOGIN_AUTOLOAD ) {
			return true;
		}

		// We use strauss to prefix our production dependencies.
		$autoloader = dirname( __DIR__ ) . '/vendor-prefixed/autoload.php';

		if ( ! is_readable( $autoloader ) ) {
			self::missing_autoloader_notice();
			return false;
		}

		$loaded = require_once $autoloader; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable

		// Then we load our regular dependencies.
		if ( $loaded ) {
			$autoloader = dirname( __DIR__ ) . '/vendor/autoload.php';

			if ( ! is_readable( $autoloader ) ) {
				self::missing_autoloader_notice();
				return false;
			}

			$loaded = require_once $autoloader; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable
		}

		if ( ! $loaded ) {
			return false;
		}

		return $loaded;
	}

	/**
	 * Displays a notice if the autoloader is missing.
	 */
	protected static function missing_autoloader_notice(): void {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				esc_html__( 'Headless Login for WPGraphQL: The Composer autoloader was not found. If you installed the plugin from the GitHub source, make sure to run `composer install`.', 'wp-graphql-headless-login' )
			);
		}

		add_action(
			'admin_notices',
			static function () {
				?>
				<div class="error notice">
					<p>
						<?php
							esc_html__( 'Headless Login for WPGraphQL: The Composer autoloader was not found. If you installed the plugin from the GitHub source, make sure to run `composer install`.', 'wp-graphql-headless-login' )
						?>
					</p>
				</div>
				<?php
			}
		);
	}
}
