<?php
/**
 * Registers the RevokeUserSecret mutation
 *
 * @package WPGraphQL\Login\Mutation
 * @since 0.0.1
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Mutation;

use GraphQL\Error\UserError;
use GraphQL\Type\Definition\ResolveInfo;
use WPGraphQL\AppContext;
use WPGraphQL\Login\Auth\TokenManager;
use WPGraphQL\Login\Vendor\AxeWP\GraphQL\Abstracts\MutationType;
use WPGraphQL\Utils\Utils as GraphQL_Utils;

/**
 * Class - RevokeUserSecret
 */
class RevokeUserSecret extends MutationType {
	/**
	 * {@inheritDoc}
	 */
	public static function type_name(): string {
		return 'RevokeUserSecret';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array{userId: array{type: array{non_null: string}, description: string}}
	 */
	public static function get_input_fields(): array {
		return [
			'userId' => [
				'type'        => [ 'non_null' => 'ID' ],
				'description' => __( 'The WordPress user ID. Accepts either a database or global ID.', 'wp-graphql-headless-login' ),
			],
		];
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array{revokedUserSecret: array{type: string, description: string}, success: array{type: string, description: string}}
	 */
	public static function get_output_fields(): array {
		return [
			'revokedUserSecret' => [
				'type'        => 'String',
				'description' => __( 'The revoked user secret.', 'wp-graphql-headless-login' ),
			],
			'success'           => [
				'type'        => 'Boolean',
				'description' => __( 'Whether the User secret was successfully revoked.', 'wp-graphql-headless-login' ),
			],
		];
	}

	/**
	 * Gets the `mutateAndGetPayload` callable for the mutation.
	 */
	public static function mutate_and_get_payload(): callable {
		return static function ( array $input, AppContext $context, ResolveInfo $info ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
			$user_id = GraphQL_Utils::get_database_id_from_id( esc_attr( $input['userId'] ) );

			$user = ! empty( $user_id ) ? get_user_by( 'id', $user_id ) : false;

			if ( empty( $user_id ) || empty( $user ) || ! TokenManager::current_user_can( $user_id, false ) ) {
				throw new UserError( esc_html__( 'You are not allowed to revoke the user secret.', 'wp-graphql-headless-login' ) );
			}

			// Revoke the user secret.
			$revoked_secret = TokenManager::get_user_secret( $user_id, true );
			$is_revoked     = TokenManager::revoke_user_secret( $user_id, false );

			if ( is_wp_error( $is_revoked ) ) {
				graphql_debug( $is_revoked->get_error_message() );
				return [ 'success' => false ];
			}

			return [
				'success'           => $is_revoked,
				'revokedUserSecret' => $revoked_secret,
			];
		};
	}
}
