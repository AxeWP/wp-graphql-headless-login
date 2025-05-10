<?php
/**
 * Registers the Logout mutation.
 *
 * @package WPGraphQL\Logout\Mutation
 * @since 0.4.0
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Mutation;

use GraphQL\Type\Definition\ResolveInfo;
use WPGraphQL\AppContext;
use WPGraphQL\Login\Utils\Utils;
use WPGraphQL\Login\Vendor\AxeWP\GraphQL\Abstracts\MutationType;

/**
 * Class - Logout
 */
class Logout extends MutationType {
	/**
	 * {@inheritDoc}
	 */
	public static function type_name(): string {
		return 'Logout';
	}

	/**
	 * {@inheritDoc}
	 *
	 * Overloaded to register the mutation conditionally.
	 */
	public static function register(): void {
		// Only register the mutation if the setting is enabled.
		if ( ! Utils::get_cookie_setting( 'hasLogoutMutation' ) ) {
			return;
		}

		parent::register();
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_description(): string {
		return __( 'Logs the user out of the site.', 'wp-graphql-headless-login' );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_input_fields(): array {
		// @todo add option to log out all sessions.
		return [];
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_output_fields(): array {
		return [
			'success' => [
				'type'        => 'Boolean',
				'description' => static fn () => __( 'Whether the user was successfully logged out. Will return null if the user is not logged in.', 'wp-graphql-headless-login' ),
				'resolve'     => static function ( $payload ) {
					return $payload['success'] ?? null;
				},
			],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public static function mutate_and_get_payload(): callable {
		return static function ( array $input, AppContext $context, ResolveInfo $info ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

			// Bail if the user is not logged in.
			if ( ! is_user_logged_in() ) {
				return [];
			}

			// Logout and destroy session.
			wp_logout();
			return [ 'success' => true ];
		};
	}
}
