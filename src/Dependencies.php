<?php
/**
 * Checks for plugin dependencies and conflicts
 *
 * @package WPGraphQL\Login
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login;

/**
 * Class - Dependencies
 */
final class Dependencies {
	/**
	 * Checks all dependencies, displaying notices for any issues.
	 */
	public static function check(): bool {
		$not_ready = self::dependencies_not_ready();

		if ( ! empty( $not_ready ) ) {
			foreach ( $not_ready as $plugin => $version ) {
				self::admin_notice(
					static fn (): string => sprintf(
						/* translators: 1: Plugin name, 2: Required version */
						__( '%1$s (v%2$s) must be active for Headless Login for WPGraphqL to work.', 'wp-graphql-headless-login' ),
						$plugin,
						$version
					)
				);
			}
		}

		$conflicts = self::plugin_conflicts();

		if ( ! empty( $conflicts ) ) {
			foreach ( $conflicts as $plugin ) {
				self::admin_notice(
					static fn (): string => sprintf(
						/* translators: 1: Plugin name */
						__( 'The plugin "%1$s" is known to conflict with Headless Login for WPGraphQL.', 'wp-graphql-headless-login' ),
						$plugin
					)
				);
			}
		}

		return empty( $not_ready ) && empty( $conflicts );
	}

	/**
	 * Checks if all the the required plugins are installed and activated.
	 *
	 * @since 0.0.1
	 *
	 * @return array<string,string>
	 */
	private static function dependencies_not_ready(): array {
		/**
		 * List of plugin dependencies with their required versions and callbacks to check if they are met.
		 *
		 * @var array<string,array{version:string,callback:callable}>
		 */
		$dependencies = [
			'WPGraphQL' => [
				'version'  => '2.14.1',
				'callback' => static function ( $version ) {
					return class_exists( 'WPGraphQL' ) && version_compare( WPGRAPHQL_VERSION, $version, '>=' );
				},
			],
		];

		$failing = [];

		foreach ( $dependencies as $plugin => $data ) {
			if ( ! $data['callback']( $data['version'] ) ) {
				$failing[ $plugin ] = $data['version'];
			}
		}

		return $failing;
	}

	/**
	 * Checks if any known plugin conflicts are present.
	 *
	 * @since 0.0.4
	 *
	 * @return string[]
	 */
	private static function plugin_conflicts(): array {
		/**
		 * List of known plugin conflicts and their callbacks to check if they are present.
		 *
		 * @var array<string,callable>
		 */
		$dependencies = [ // phpcs:ignore PSR2.Classes.PropertyDeclaration.Multiple -- It's a single array.
			'WPGraphQL JWT Authentication' => static function () {
				return class_exists( 'WPGraphQL\JWT_Authentication\JWT_Authentication' ) && is_plugin_active( 'wp-graphql-jwt-authentication/wp-graphql-jwt-authentication.php' );
			},
			'WPGraphQL CORS'               => static function () {
				return class_exists( 'WP_GraphQL_CORS' ) && is_plugin_active( 'wp-graphql-cors/wp-graphql-cors.php' );
			},
		];

		$conflicts = [];

		foreach ( $dependencies as $plugin => $callback ) {
			if ( $callback() ) {
				$conflicts[] = $plugin;
			}
		}

		return $conflicts;
	}

	/**
	 * Helper to display admin notice on both the admin and network admin screens.
	 *
	 * The message is resolved lazily, since translations can't be loaded before `init`.
	 *
	 * @param callable():string $message Callback returning the message to display in the admin notice.
	 */
	private static function admin_notice( callable $message ): void {
		foreach ( [ 'admin_notices', 'network_admin_notices' ] as $hook ) {
			add_action(
				$hook,
				static function () use ( $message ) {
					wp_admin_notice(
						esc_html( $message() ),
						[ 'type' => 'error' ]
					);
				}
			);
		}
	}
}
