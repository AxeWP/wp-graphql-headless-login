<?php
/**
 * Adds filters that modify core schema.
 *
 * @package WPGraphQL\Login
 */

declare( strict_types = 1 );

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
	 * @param string $token the token to return.
	 * @param int    $user_id the user ID.
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
}
