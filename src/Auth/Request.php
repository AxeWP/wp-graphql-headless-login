<?php
/**
 * Handles the GraphQL Request.
 *
 * @package WPGraphQL\Login\Auth
 * @since 0.0.6
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Auth;

use GraphQL\Error\UserError;
use WPGraphQL;
use WPGraphQL\Login\Auth\ProviderConfig\SiteToken;
use WPGraphQL\Login\Utils\Utils;

/**
 * Class - Request
 */
class Request {
	/**
	 * Authenticates the request before GraphQL is executed.
	 *
	 * @throws \GraphQL\Error\UserError If the request is from an unauthorized origin.
	 */
	public static function authenticate_token_on_request(): void {
		// If a token is present, validate it.
		$token = TokenManager::validate_token();

		if ( is_wp_error( $token ) ) {
			// We only log this in debug mode, and allow the request to continue as unauthenticated.
			graphql_debug( $token->get_error_code() . ' | ' . $token->get_error_message() );
		}
	}

	/**
	 * Authenticates the request origin when GraphQL is executed.
	 *
	 * @throws \GraphQL\Error\UserError If the request is from an unauthorized origin.
	 */
	public static function authenticate_origin_on_request(): void {
		// If we block unauthorized origins, check the origin.
		if ( Utils::get_access_control_setting( 'shouldBlockUnauthorizedDomains' ) ) {
			$allowed_origins = self::get_allowed_origins();

			$origin = self::get_origin_for_request( $allowed_origins );

			// If we didn't match, bail.
			if ( empty( $origin ) ) {
				// Set WP GraphQL status to 403.
				add_filter(
					'graphql_response_status_code',
					static fn () => 403
				);

				throw new UserError( esc_html__( 'Unauthorized request origin.', 'wp-graphql-headless-login' ) );
			}
		}
	}

	/**
	 * Filters the GraphQL response headers.
	 *
	 * @param array<string,string> $headers The headers to send.
	 *
	 * @return array<string,string|null> The filtered headers.
	 */
	public static function response_headers_to_send( array $headers ): array {
		$allowed_origins = self::get_allowed_origins( $headers );
		$origin          = self::get_origin_for_request( $allowed_origins );

		// Get Access-Control-Allow-Origin header.
		$headers['Access-Control-Allow-Origin'] = self::get_acao_header( $headers, $allowed_origins, $origin );

		// Get Access-Control-Allow-Headers header.
		$headers['Access-Control-Allow-Headers'] = self::get_acah_header( $headers );

		// Set the Access-Control-Allow-Credentials header if we have a real origin and the user enabled it.
		if ( '*' !== $headers['Access-Control-Allow-Origin'] && self::has_acac_header() ) {
			$headers['Access-Control-Allow-Credentials'] = 'true';
		}

		// Get Vary header.
		$headers['Vary'] = self::get_vary_header( $headers, $allowed_origins );

		// Add login and refresh tokens.
		$headers = self::add_tokens_to_headers( $headers, $origin );

		/**
		 * Get Access-Control-Expose-Headers header.
		 *
		 * This is called after the tokens are set, so we know whether to expose them.
		 */
		$headers['Access-Control-Expose-Headers'] = self::get_aceh_header( $headers );

		return $headers;
	}

	/**
	 * Expose the X-WPGraphQL-Login-Refresh-Token tokens in the response headers.
	 *
	 * This allows clients the ability to Authenticate with WPGraphQL, use the token with REST API Requests, but get new refresh tokens from the REST API Headers.
	 *
	 * @param mixed $response Response object.
	 *
	 * @return mixed
	 */
	public static function add_headers_to_rest_response( $response ) {
		if ( ! $response instanceof \WP_HTTP_Response ) {
			return $response;
		}

		// Bail early if not ssl or if debugging is enabled.
		if ( ! is_ssl() && false === WPGraphQL::debug() ) {
			return $response;
		}

		$headers = $response->get_headers();

		$headers = self::response_headers_to_send( $headers );

		$response->set_headers( $headers );

		return $response;
	}

	/**
	 * Returns the headers with the Access-Control-Allow-Orgin set.
	 *
	 * @param array<string,string> $headers         The headers to send.
	 * @param string[]             $allowed_origins The allowed origins.
	 * @param ?string              $origin          The origin to use.
	 */
	protected static function get_acao_header( array $headers, array $allowed_origins, ?string $origin ): string {
		// If we matched, return the origin.
		if ( ! empty( $origin ) ) {
			return $origin;
		}

		// If unauthorized origins are allowed, return the wildcard.
		if ( ! Utils::get_access_control_setting( 'shouldBlockUnauthorizedDomains' ) ) {
			return '*';
		}

		// Fall back to the first allowed origin.
		return $allowed_origins[0];
	}

	/**
	 * Gets the allowed origin domains.
	 *
	 * @param array<string,string> $headers The headers to send. Optional.
	 *
	 * @return string[] The allowed origin domains.
	 */
	protected static function get_allowed_origins( $headers = [] ): array {
		$origins = [
			get_option( 'siteurl' ), // The WordPress Address is used for local POST requests.
		];

		if ( Utils::get_access_control_setting( 'hasSiteAddressInOrigin' ) ) {
			$origins[] = get_option( 'home' ); // The Site Address is used for remote POST requests. E.g. when using a different URL for the frontend.
		}

		// Get the origin from the existing header filters.
		if ( ! empty( $headers['Access-Control-Allow-Origin'] ) && '*' !== $headers['Access-Control-Allow-Origin'] ) {
			$origins[] = $headers['Access-Control-Allow-Origin'];
		}

		$additional_origins = Utils::get_access_control_setting( 'additionalAuthorizedDomains' );
		if ( ! empty( $additional_origins ) ) {
			$origins = array_merge( $origins, array_map( 'trim', $additional_origins ) );
		}

		// Remove empty values.
		$origins = array_filter( array_unique( $origins ) );

		return $origins;
	}

	/**
	 * Gets the origin for the current request.
	 *
	 * @param string[] $origins The allowed origins.
	 */
	protected static function get_origin_for_request( array $origins ): ?string {
		$current_origin = get_http_origin();
		if ( empty( $current_origin ) ) {
			$current_origin = ! empty( $_SERVER['HTTP_REFERER'] ) ? sanitize_text_field( $_SERVER['HTTP_REFERER'] ) : null;
		}

		// Unslash the origin.
		$current_host = ! empty( $current_origin ) ? wp_unslash( $current_origin ) : null;
		// Get the host name.
		$current_host = ! empty( $current_host ) ? wp_parse_url( $current_host, PHP_URL_HOST ) : null;

		// If the request origin is not set, return null.
		if ( empty( $current_host ) || ! is_string( $current_host ) ) {
			return null;
		}

		foreach ( $origins as $origin ) {
			$allowed_host = wp_parse_url( $origin, PHP_URL_HOST );

			// Skip if the allowed origin isn't valid.
			if ( empty( $allowed_host ) || ! is_string( $allowed_host ) ) {
				continue;
			}

			// If the current host matches the allowed host, return the origin.
			if ( $current_host === $allowed_host ) {
				return $origin;
			}
		}

		return null;
	}

	/**
	 * Gets the Vary header.
	 *
	 * @param array<string,string> $headers         The headers to send.
	 * @param string[]             $allowed_origins The allowed origins.
	 */
	protected static function get_vary_header( array $headers, array $allowed_origins ): string {
		// Bail early if we only have one possible origin.
		if ( count( $allowed_origins ) === 1 && $headers['Access-Control-Allow-Origin'] === $allowed_origins[0] ) {
			return $headers['Vary'] ?? '';
		}

		$vary = [ 'Origin' ];

		// If headers are already set, merge them.
		if ( ! empty( $headers['Vary'] ) ) {
			$vary = array_merge( $vary, explode( ', ', $headers['Vary'] ) );
		}

		// Remove empty values.
		$vary = array_filter( array_unique( $vary ) );

		return implode( ', ', $vary );
	}

	/**
	 * Gets the Access-Control-Allow-Headers header.
	 *
	 * @param array<string,string> $header The headers to send.
	 */
	protected static function get_acah_header( array $header ): string {
		$headers = [
			'Authorization',
			'Content-Type',
			'X-WPGraphQL-Login-Token',
			'X-WPGraphQL-Login-Refresh-Token',
		];

		// If headers are already set, merge them.
		if ( ! empty( $header['Access-Control-Allow-Headers'] ) ) {
			$headers = array_merge( $headers, explode( ', ', $header['Access-Control-Allow-Headers'] ) );
		}

		// If the SiteLogin provider is active, then set add the provider's header key.
		if ( SiteToken::is_enabled() ) {
			$options = Utils::get_provider_settings( SiteToken::get_slug() );

			$token_header = $options['clientOptions']['headerKey'] ?? null;

			if ( ! empty( $token_header ) ) {
				$headers[] = $token_header;
			}
		}

		// Add custom headers.
		$custom_headers = Utils::get_access_control_setting( 'customHeaders', [] );
		if ( ! empty( $custom_headers ) && is_array( $custom_headers ) ) {
			$headers = array_merge( $headers, $custom_headers );
		}

		// Remove empty and duplicates.
		$headers = array_filter( array_unique( $headers ) );

		return implode( ', ', $headers );
	}

	/**
	 * Gets the Access-Control-Expose-Headers header.
	 *
	 * @param array<string,string|null> $header The headers to send.
	 */
	protected static function get_aceh_header( array $header ): string {
		$exposed_headers = ! empty( $header['X-WPGraphQL-Login-Refresh-Token'] ) ? [
			'X-WPGraphQL-Login-Refresh-Token',
		] : [];

		// If headers are already set, merge them.
		if ( ! empty( $header['Access-Control-Expose-Headers'] ) ) {
			$exposed_headers = array_merge( $exposed_headers, explode( ', ', $header['Access-Control-Expose-Headers'] ) );
		}

		// Remove empty and duplicates.
		$exposed_headers = array_filter( array_unique( $exposed_headers ) );

		return implode( ', ', $exposed_headers );
	}

	/**
	 * Checks whether the Access-Control-Allow-Credentials header should be sent.
	 */
	protected static function has_acac_header(): bool {
		return (bool) Utils::get_access_control_setting( 'hasAccessControlAllowCredentials' );
	}

	/**
	 * Adds the auth tokens to the GraphQL response headers.
	 *
	 * @param array<string,string> $headers The headers to send.
	 * @param ?string              $origin  The origin for the current request.
	 *
	 * @return array<string,string|null>
	 */
	protected static function add_tokens_to_headers( array $headers, ?string $origin ): array {
		// Bail early if not ssl or if debugging is disabled.
		if ( ! is_ssl() && false === WPGraphQL::debug() ) {
			return $headers;
		}

		// Bail early if the origin is not allowed.
		if ( Utils::get_access_control_setting( 'shouldBlockUnauthorizedDomains' ) && empty( $origin ) ) {
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
}
