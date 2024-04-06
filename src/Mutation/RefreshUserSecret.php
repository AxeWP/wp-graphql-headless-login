<?php
/**
 * Registers the RefreshUserSecret mutation
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
use WPGraphQL\Utils\Utils as WPGraphQL_Utils;

/**
 * Class - RefreshUserSecret
 */
class RefreshUserSecret extends MutationType {
	/**
	 * {@inheritDoc}
	 */
	public static function type_name(): string {
		return 'RefreshUserSecret';
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_input_fields(): array {
		return [
			'userId' => [
				'type'        => [ 'non_null' => 'ID' ],
				'description' => __( 'The current WordPress user ID. Accepts either a global or database ID.', 'wp-graphql-headless-login' ),
			],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_output_fields(): array {
		return [
			'success'           => [
				'type'        => 'Boolean',
				'description' => __( 'Whether the User secret was successfully revoked.', 'wp-graphql-headless-login' ),
			],
			'revokedUserSecret' => [
				'type'        => 'String',
				'description' => __( 'The revoked user secret.', 'wp-graphql-headless-login' ),
			],
			'userSecret'        => [
				'type'        => 'String',
				'description' => __( 'The new user secret.', 'wp-graphql-headless-login' ),
			],
			'authToken'         => [
				'type'        => 'String',
				'description' => __( 'JWT Token that can be used in future requests for Authentication.', 'wp-graphql-headless-login' ),
			],
			'refreshToken'      => [
				'type'        => 'String',
				'description' => __( 'JWT Token that can be used in future requests for Authentication.', 'wp-graphql-headless-login' ),
			],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public static function mutate_and_get_payload(): callable {
		return static function ( array $input, AppContext $context, ResolveInfo $info ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
			$user_id = WPGraphQL_Utils::get_database_id_from_id( esc_attr( $input['userId'] ) );

			$user = ! empty( $user_id ) ? get_user_by( 'id', $user_id ) : false;

			if ( empty( $user_id ) || empty( $user ) || ! TokenManager::current_user_can( $user_id, false ) ) {
				throw new UserError( esc_html__( 'You are not allowed to refresh the user secret.', 'wp-graphql-headless-login' ) );
			}

			$revoked_secret = TokenManager::get_user_secret( $user_id, true );
			$is_refreshed   = TokenManager::refresh_user_secret( $user_id, false );

			if ( is_wp_error( $is_refreshed ) ) {
				graphql_debug( $is_refreshed->get_error_message() );
				return [ 'success' => false ];
			}

			if ( ! $is_refreshed ) {
				graphql_debug( __( 'User secret could not be refreshed.', 'wp-graphql-headless-login' ) );
				return [ 'success' => false ];
			}

			// Generate new tokens.
			$user          = new \WP_User( $user_id );
			$secret        = TokenManager::get_user_secret( $user_id, true );
			$auth_token    = TokenManager::get_auth_token( $user, true );
			$refresh_token = TokenManager::get_refresh_token( $user, true );

			return [
				'success'           => $is_refreshed,
				'authToken'         => $auth_token,
				'revokedUserSecret' => $revoked_secret,
				'refreshToken'      => $refresh_token,
				'userSecret'        => $secret,
			];
		};
	}
}
