<?php
/**
 * Adds filters that modify core schema.
 *
 * @package WPGraphQL\Login
 */

namespace WPGraphQL\Login;

use GraphQL\Error\UserError;
use WPGraphQL\Login\Auth\Request;
use WPGraphQL\Login\Auth\TokenManager;
use WPGraphQL\Login\Model\User;
use WPGraphQL\Login\Vendor\AxeWP\GraphQL\Interfaces\Registrable;

/**
 * Class - CoreSchemaFilters
 */
class CoreSchemaFilters implements Registrable {
	/**
	 * {@inheritDoc}
	 */
	public static function init(): void {
		// Prefix the GraphQL type names.
		add_filter( 'graphql_login_type_prefix', [ self::class, 'get_type_prefix' ] );

		// Filter how WordPress determines the current user.
		add_filter( 'determine_current_user', [ self::class, 'determine_current_user' ], 99 );

		// Extend the User model to hold authentication data.
		add_filter( 'graphql_model_prepare_fields', [ User::class, 'get_fields' ], 10, 3 );

		// Filter the signed token, preventing it from returning if the user has had their JWT Secret revoked.
		add_filter( 'graphql_login_signed_token', [ self::class, 'check_if_secret_is_revoked' ], 10, 2 );

		// Filter the GraphQL response headers.
		add_filter( 'graphql_response_headers_to_send', [ Request::class, 'response_headers_to_send' ] );
		// Apply headers to REST request responses.
		add_filter( 'rest_request_after_callbacks', [ Request::class, 'add_headers_to_rest_response' ], 10 );

		// When the GraphQL Request is initiated, validate the token.
		add_action( 'init_graphql_request', [ Request::class, 'authenticate_token_on_request' ] );
		// When the GraphQL Request is executed, validate the origin.
		add_action( 'do_graphql_request', [ Request::class, 'authenticate_origin_on_request' ] );
	}

	/**
	 * Don't prefix type names.
	 */
	public static function get_type_prefix(): string {
		return '';
	}

	/**
	 * Checks if the user has had their JWT Secret revoked, before returning the token.
	 *
	 * @param string  $token the token to return.
	 * @param integer $user_id the user ID.
	 *
	 * @throws \GraphQL\Error\UserError If the user has had their JWT Secret revoked.
	 */
	public static function check_if_secret_is_revoked( string $token, int $user_id ): string {
		$is_revoked = TokenManager::is_user_secret_revoked( $user_id );

		if ( $is_revoked ) {
			throw new UserError( esc_html__( 'A JWT token cannot be issued for this user.', 'wp-graphql-headless-login' ) );
		}

		return $token;
	}

	/**
	 * Filters the current user for the request.
	 *
	 * @param int $user_id The user ID.
	 */
	public static function determine_current_user( int $user_id ): int {
		// Validate the token.
		$token = TokenManager::validate_token();

		// If the token is invalid, return the existing user.
		if ( empty( $token ) || is_wp_error( $token ) ) {
			return $user_id;
		}

		// Get the user from the token.
		$user_id = empty( $token->data->user->id ) ? $user_id : $token->data->user->id;

		return absint( $user_id );
	}
}
