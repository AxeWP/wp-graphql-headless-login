<?php
/**
 * Registers the RefreshToken mutation
 *
 * @package WPGraphQL\Login\Mutation
 * @since 0.0.1
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Mutation;

use GraphQL\Type\Definition\ResolveInfo;
use WPGraphQL\AppContext;
use WPGraphQL\Login\Auth\TokenManager;
use WPGraphQL\Login\Auth\User;
use WPGraphQL\Login\Vendor\AxeWP\GraphQL\Abstracts\MutationType;
/**
 * Class - RefreshToken
 */
class RefreshToken extends MutationType {
	/**
	 * {@inheritDoc}
	 */
	public static function type_name(): string {
		return 'RefreshToken';
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_input_fields(): array {
		return [
			'refreshToken' => [
				'type'        => [ 'non_null' => 'String' ],
				'description' => __( 'A valid, previously issued JWT refresh token. If valid, a new JWT authentication token will be provided. If invalid, expired, revoked or otherwise invalid, the `authToken` will return null, and the `success` field will return `false`.', 'wp-graphql-headless-login' ),
			],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_output_fields(): array {
		return [
			'authToken'           => [
				'type'        => 'String',
				'description' => __( 'JWT Token that can be used in future requests for Authentication.', 'wp-graphql-headless-login' ),
			],
			'authTokenExpiration' => [
				'type'        => 'String',
				'description' => __( 'The authentication token expiration timestamp.', 'wp-graphql-headless-login' ),
			],
			'success'             => [
				'type'        => 'Boolean',
				'description' => __( 'Whether the auth token was successfully refreshed.', 'wp-graphql-headless-login' ),
			],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public static function mutate_and_get_payload(): callable {
		return static function ( array $input, AppContext $context, ResolveInfo $info ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
			// Sanitize the refresh token.
			$sanitized_token = sanitize_text_field( $input['refreshToken'] );

			$refresh_token = empty( $sanitized_token ) ? null : TokenManager::validate_token( $sanitized_token, true );

			if ( is_wp_error( $refresh_token ) ) {
				graphql_debug( $refresh_token->get_error_message() );

				return [
					'authToken' => null,
					'success'   => false,
				];
			}

			// Try to get the User from the refresh token.
			$user_id = empty( $refresh_token->data->user->id ) ? null : absint( $refresh_token->data->user->id );

			$user = ! empty( $user_id ) ? get_user_by( 'id', $user_id ) : false;

			if ( empty( $refresh_token ) || empty( $user ) ) {
				graphql_debug( __( 'The provided refresh token is invalid.', 'wp-graphql-headless-login' ) );

				return [ 'success' => false ];
			}

			// Generate a new auth token.
			wp_set_current_user( $user_id );

			$user       = new \WP_User( $user_id );
			$auth_token = TokenManager::get_auth_token( $user, false );
			$expiration = User::get_auth_token_expiration( $user->ID );

			return [
				'authToken'           => $auth_token,
				'authTokenExpiration' => $expiration,
				'success'             => ! empty( $auth_token ),
			];
		};
	}
}
