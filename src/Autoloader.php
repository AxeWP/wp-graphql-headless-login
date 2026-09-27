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
	 * Attempts to autoload the Composer dependencies.
	 */
	public static function autoload(): bool {
		// If we're not *supposed* to autoload anything, then return true.
		if ( defined( 'WPGRAPHQL_LOGIN_AUTOLOAD' ) && false === WPGRAPHQL_LOGIN_AUTOLOAD ) {
			return true;
		}

		// Load prefixed dependencies first (Strauss-generated).
		if ( ! self::require_autoloader( WPGRAPHQL_LOGIN_PLUGIN_DIR . 'vendor-prefixed/autoload.php' ) ) {
			return false;
		}

		return self::require_autoloader( WPGRAPHQL_LOGIN_PLUGIN_DIR . '/vendor/autoload.php' );
	}

	/**
	 * Attempts to load the autoloader file, if it exists.
	 *
	 * @param string $autoloader_file The path to the autoloader file.
	 *
	 * @return bool Whether the autoloader was successfully loaded.
	 */
	private static function require_autoloader( string $autoloader_file ): bool {
		// Use a local static variable to track if the autoloader has already been loaded.
		static $loaded = [];

		if ( isset( $loaded[ $autoloader_file ] ) ) {
			return $loaded[ $autoloader_file ];
		}

		if ( ! is_readable( $autoloader_file ) ) {
			self::missing_autoloader_notice();

			return false;
		}

		$loaded[ $autoloader_file ] = (bool) require_once $autoloader_file; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable -- Autoloader is a Composer file.

		return $loaded[ $autoloader_file ];
	}

		/**
		 * Displays a notice if the autoloader is missing.
		 */
	private static function missing_autoloader_notice(): void {
		$hooks = [
			'admin_notices',
			'network_admin_notices',
		];

		foreach ( $hooks as $hook ) {
			add_action(
				$hook,
				static function (): void {
					$error_message = self::get_autoloader_error_message();
					_doing_it_wrong( self::class, esc_html( $error_message ), '0.1.0' );

					// Display the error notice in the admin.
					wp_admin_notice(
						esc_html( $error_message ),
						[
							'type'    => 'error',
							'dismiss' => false,
						]
					);
				}
			);
		}
	}

	/**
	 * The error message to display when the autoloader errors.
	 *
	 * We stick it in a function, so it's available to `missing_autoloader_notice()` without prop drilling into the hook.
	 */
	private static function get_autoloader_error_message(): string {
		return __( 'Headless Login for WPGraphQL: The Composer autoloader was not found. If you installed the plugin from the GitHub source, make sure to install and build the dependencies using `composer install`.', 'wp-graphql-headless-login' );
	}
}
