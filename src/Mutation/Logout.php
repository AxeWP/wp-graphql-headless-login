<?php
/**
 * Registers the Logout mutation
 *
 * @package WPGraphQL\Logout\Mutation
 * @since @todo
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
		if ( ! Utils::get_access_control_setting( 'hasLogoutMutation' ) ) {
			return;
		}

		parent::register();
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
			'status' => [
				'type'        => 'String',
				'description' => 'Logout operation status',
				'resolve'     => static function ( $payload ) {
					return $payload['status'];
				},
			],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public static function mutate_and_get_payload(): callable {
		return static function ( array $input, AppContext $context, ResolveInfo $info ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
			// Logout and destroy session.
			wp_logout();
			return [ 'status' => 'SUCCESS' ];
		};
	}
}
