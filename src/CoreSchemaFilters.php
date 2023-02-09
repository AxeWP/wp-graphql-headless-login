<?php
/**
 * Adds filters that modify core schema.
 *
 * @package WPGraphQL\Login
 */

namespace WPGraphQL\Login;

use GraphQL\Error\UserError;
use WPGraphQL;
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
	public static function init() : void {
		// Prefix the GraphQL type names.
		add_filter( 'graphql_login_type_prefix', [ __CLASS__, 'get_type_prefix' ] );
		// Extend the User model to hold authentication data.
		add_filter( 'graphql_model_prepare_fields', [ User::class, 'get_fields' ], 10, 3 );
		// Filter the signed token, preventing it from returning if the user has had their JWT Secret revoked.
		add_filter( 'graphql_login_signed_token', [ __CLASS__, 'check_if_secret_is_revoked' ], 10, 2 );
		// Filter the GraphQL response headers.
		add_filter( 'graphql_response_headers_to_send', [ __CLASS__, 'add_tokens_to_graphql_response_headers' ] );
		add_filter( 'graphql_response_headers_to_send', [ __CLASS__, 'add_refresh_token_to_headers' ] );
		/**
		 * Add Auth Headers to REST REQUEST responses
		 *
		 * This allows clients to use our JWT tokens tokens with WPGraphQL _and_ with REST API requests, and exposes refresh tokens in the REST API response so folks can refresh their tokens after each REST API request.
		 */
		add_filter( 'rest_request_after_callbacks', [ __CLASS__, 'add_auth_headers_to_rest_response' ], 10, 1 );

		// Filter allowed HTTP headers.
		add_filter( 'graphql_access_control_allow_headers', [ __CLASS__, 'add_allowed_headers' ] );

		// Filter how WordPress determines the current user.
		add_filter( 'determine_current_user', [ __CLASS__, 'determine_current_user' ], 99 );

		// When the GraphQL Request is initiated, validate the token.
		add_action( 'init_graphql_request', [ __CLASS__, 'authenticate_before_resolve' ] );
	}

	/**
	 * Don't prefix type names.
	 */
	public static function get_type_prefix() : string {
		return '';
	}

	/**
	 * Checks if the user has had their JWT Secret revoked, before returning the token.
	 *
	 * @param string  $token the token to return.
	 * @param integer $user_id the user ID.
	 *
	 * @throws UserError If the user has had their JWT Secret revoked.
	 */
	public static function check_if_secret_is_revoked( string $token, int $user_id ) : string {
		$is_revoked = TokenManager::is_user_secret_revoked( $user_id );

		if ( $is_revoked ) {
			throw new UserError( __( 'A JWT token cannot be issued for this user.', 'wp-graphql-headless-login' ) );
		}

		return $token;
	}

	/**
	 * Adds the auth tokens to the GraphQL response headers.
	 *
	 * @param array $headers the headers to send.
	 */
	public static function add_tokens_to_graphql_response_headers( $headers ) : array {
		// Bail early if not ssl or if debugging is disabled.
		if ( ! is_ssl() && false === WPGraphQL::debug() ) {
			return $headers;
		}

		$refresh_token = null;
		// Get the auth token from the request.
		$validate_auth_header = TokenManager::validate_token();

		// If the auth token is invalid or expired, bail early.
		if ( is_wp_error( $validate_auth_header ) || empty( $validate_auth_header->data->user->id ) ) {
			return $headers;
		}

		$user = get_user_by( 'ID', $validate_auth_header->data->user->id );

		if ( empty( $user ) ) {
			return $headers;
		}

		// Generate new refresh token.
		$refresh_token = TokenManager::get_refresh_token( $user, false );

		$validate_refresh_token = TokenManager::validate_token( $refresh_token, true );

		// If the refresh token is invalid or expired, bail early.
		if ( is_wp_error( $validate_refresh_token ) || empty( $validate_refresh_token->data->user->id ) || $validate_refresh_token->data->user->id !== $validate_auth_header->data->user->id ) {
			return $headers;
		}

		// Add new auth token and generated refresh token to the headers.
		$headers['X-WPGraphQL-Login-Token']         = TokenManager::get_auth_token( $user, false );
		$headers['X-WPGraphQL-Login-Refresh-Token'] = $refresh_token;

		return $headers;
	}

	/**
	 * Expose the X-WPGraphQL-Login-Refresh-Token tokens in the response headers. This allows
	 * folks to grab new refresh tokens from authenticated requests for subsequent use.
	 *
	 * @param array $headers The existing response headers.
	 */
	public static function add_refresh_token_to_headers( array $headers ) : array {
		if ( empty( $headers['Access-Control-Expose-Headers'] ) ) {
			$headers['Access-Control-Expose-Headers'] = 'X-WPGraphQL-Login-Refresh-Token';
		} else {
			$headers['Access-Control-Expose-Headers'] .= ', X-WPGraphQL-Login-Refresh-Token';
		}

		return $headers;
	}

	/**
	 * Expose the X-WPGraphQL-Login-Refresh-Token tokens in the response headers.
	 *
	 * This allows clients the ability to Authenticate with WPGraphQL, use the token
	 * with REST API Requests, but get new refresh tokens from the REST API Headers
	 *
	 * @param mixed $response Response object.
	 *
	 * @return mixed
	 */
	public static function add_auth_headers_to_rest_response( $response ) {
		if ( ! $response instanceof \WP_HTTP_Response ) {
			return $response;
		}

		// Bail early if not ssl or if debugging is enabled.
		if ( ! is_ssl() && ( ! defined( 'GRAPHQL_DEBUG' ) || true !== GRAPHQL_DEBUG ) ) {
			return $response;
		}

		$headers = $response->get_headers();

		$expose_headers = explode( ', ', $headers['Access-Control-Expose-Headers'] ?? '' );
		$expose_headers = [ ...$expose_headers, 'X-WPGraphQL-Login-Refresh-Token' ];

		$response->set_headers(
			[
				'Access-Control-Expose-Headers' => implode( ', ', $expose_headers ),
			]
		);

		$refresh_token = null;

		$validate_auth_header = TokenManager::validate_token( str_ireplace( 'Bearer ', '', TokenManager::get_auth_header() ), false );

		if ( ! is_wp_error( $validate_auth_header ) && ! empty( $validate_auth_header->data->user->id ) ) {
			$refresh_token = TokenManager::get_refresh_token( new \WP_User( $validate_auth_header->data->user->id ), false );
		}

		if ( $refresh_token ) {
			$response->set_headers(
				[
					'X-WPGraphQL-Login-Refresh-Token' => $refresh_token,
				]
			);
		}

		return $response;
	}

	/**
	 * Exposes the X-WPGraphQL-Login-Token and  X-WPGraphQL-Login-Refresh-Token header in the Access-Control-Allow-Headers.
	 *
	 * @param array $allowed_headers The allowed headers.
	 */
	public static function add_allowed_headers( array $allowed_headers ) : array {
		$allowed_headers[] = 'X-WPGraphQL-Login-Token';
		$allowed_headers[] = 'X-WPGraphQL-Login-Refresh-Token';

		return $allowed_headers;
	}

	/**
	 * Filters the current user for the request.
	 *
	 * @param int $user_id The user ID.
	 */
	public static function determine_current_user( int $user_id ) : int {
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

	/**
	 * Authenticates the token before resolving the GraphQL fields.
	 */
	public static function authenticate_before_resolve() : void {
		// Validate the token.
		$token = TokenManager::validate_token();

		if ( is_wp_error( $token ) ) {
			graphql_debug( $token->get_error_code() . ' | ' . $token->get_error_message() );
		}
	}
}
