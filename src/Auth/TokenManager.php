<?php
/**
 * Handles the creation, removal, and validation of JWT tokens.
 *
 * @package WPGraphQL\Login\Auth
 * @since 0.0.1
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Auth;

use WPGraphQL\Login\Utils\Utils;
use WPGraphQL\Login\Vendor\Firebase\JWT\JWT;
use WPGraphQL\Login\Vendor\Firebase\JWT\Key;
use WP_Error;
use WP_User;

/**
 * Class - TokenManager
 */
class TokenManager {
	/**
	 * The token issued time.
	 *
	 * @var int
	 */
	private static int $issued_at;

	/**
	 * The token expiration time.
	 *
	 * @var int
	 */
	private static int $expiration;

	/**
	 * This returns the secret key, using the defined constant if defined, and passing it through a filter to allow for the config to be able to be set via another method other than a defined constant, such as an admin UI that allows the key to be updated/changed/revoked at any time without touching server files.
	 */
	public static function get_secret_key(): string {
		// Use the defined secret key, if it exists.
		$secret = defined( 'GRAPHQL_LOGIN_JWT_SECRET_KEY' ) && ! empty( GRAPHQL_LOGIN_JWT_SECRET_KEY ) ? GRAPHQL_LOGIN_JWT_SECRET_KEY : '';

		/**
		 * Filter the secret key used to sign the JWT token.
		 *
		 * @param string $secret The secret key.
		 */
		$secret = apply_filters( 'graphql_login_jwt_secret_key', $secret );

		// Attempt to get the secret from the settings.
		if ( empty( $secret ) ) {
			$secret = graphql_login_get_setting( 'jwt_secret_key', '' );

			// Create a new secret if one doesn't exist.
			if ( empty( $secret ) ) {
				$secret = wp_generate_password( 64, false, false );
				Utils::update_plugin_setting( 'jwt_secret_key', $secret );
			}
		}

		return $secret;
	}

	/**
	 * Gets the signed auth token for the user.
	 *
	 * @param \WP_User $user The user.
	 * @param bool     $enforce_current_user Whether to only allow the current user to be used. Default true.
	 */
	public static function get_auth_token( WP_User $user, bool $enforce_current_user = true ): ?string {
		// Get the token data for signing.
		$token = self::prepare_token( $user, $enforce_current_user );

		// Bail early if error.
		if ( is_wp_error( $token ) ) {
			// Log the error.
			graphql_debug( $token->get_error_message() );
			return null;
		}

		// Sign the token.
		$signed_token = self::sign_token( $token, $user->ID );

		if ( ! empty( $signed_token ) ) {
			// Update the token expiry in the user meta.
			User::set_auth_token_expiration( $user->ID, $token['exp'] );
		}

		return $signed_token;
	}

	/**
	 * Gets the signed refresh token for the user.
	 *
	 * @param \WP_User $user The user.
	 * @param bool     $enforce_current_user Whether to only allow the current user to be used. Default true.
	 */
	public static function get_refresh_token( WP_User $user, bool $enforce_current_user = true ): ?string {
		// Get the token data for signing.
		$token = self::prepare_token( $user, $enforce_current_user );

		// Bail early if error.
		if ( is_wp_error( $token ) ) {
			// Log the error.
			graphql_debug( $token->get_error_message() );
			return null;
		}

		// Gets the User's JWT secret.
		$secret = self::get_user_secret( $user->ID, $enforce_current_user );

		// Bail early if no/invalid secret.
		if ( empty( $secret ) ) {
			graphql_debug( __( 'No secret found for user.', 'wp-graphql-headless-login' ) );
			return null;
		}

		// Make the token long-lived.

		/**
		 * Filters the duration for which a refresh token should be considered valid.
		 *
		 * @param int $validity The validity in seconds. Defaults to 1 year.
		 */
		$validity = apply_filters( 'graphql_login_refresh_token_validity', ( DAY_IN_SECONDS * 365 ) );

		/**
		 * Filters the token expiration timestamp.
		 *
		 * @param int $timestamp The timestamp.
		 */
		$token['exp'] = apply_filters(
			'graphql_login_refresh_token_expiration_timestamp',
			self::get_issued_at() + $validity
		);
		// Add the user secret to the token.
		$token['data']['user']['user_secret'] = $secret;

		// Sign the token.
		$signed_token = self::sign_token( $token, $user->ID );

		if ( ! empty( $signed_token ) ) {
			// Update the token expiry in the user meta.
			User::set_refresh_token_expiration( $user->ID, $token['exp'] );
		}

		return $signed_token;
	}

	/**
	 * Returns the JWT secret for the user ID.
	 *
	 * @param int  $user_id The user ID.
	 * @param bool $enforce_current_user Whether to enforce the user secret. Default true.
	 */
	public static function get_user_secret( int $user_id, bool $enforce_current_user = true ): ?string {
		if ( $enforce_current_user && ! self::current_user_can( $user_id, $enforce_current_user ) ) {
			return null;
		}

		// Get the stored secret.
		$secret = User::get_secret( $user_id );

		// If the secret is empty or a bad value, issue a new one.
		if ( empty( $secret ) ) {
			$secret = self::issue_new_user_secret( $user_id, $enforce_current_user );
		}

		/**
		 * Filter the user secret before returning it, allowing for individual systems to override what's returned.
		 *
		 * @param string|\WP_Error $secret The User secret.
		 * @param int     $user_id The ID of the user the secret is associated with.
		 */
		$secret = apply_filters( 'graphql_login_user_secret', $secret, $user_id );

		return $secret instanceof \WP_Error ? null : (string) $secret;
	}

	/**
	 * Revokes the user secret.
	 *
	 * @param int  $user_id The user ID.
	 * @param bool $enforce_current_user Whether to enforce the user secret. Default true.
	 *
	 * @return true|\WP_Error
	 */
	public static function revoke_user_secret( int $user_id, bool $enforce_current_user = true ) {
		// Return an error if the user cannot revoke the secret.
		if ( ! self::current_user_can( $user_id, $enforce_current_user ) ) {
			self::set_status( 401 );
			return new WP_Error( 'graphql-headless-login-cannot-revoke-secret', __( 'The Secret cannot be revoked for this user.', 'wp-graphql-headless-login' ) );
		}

		// Set the user meta as true, marking the secret as revoked.
		User::set_is_secret_revoked( $user_id, true );

		// Success!
		return true;
	}

	/**
	 * Refreshes the user secret the user secret.
	 *
	 * @param int  $user_id The user ID.
	 * @param bool $enforce_current_user Whether to enforce the user secret. Default true.
	 *
	 * @return bool|\WP_Error
	 */
	public static function refresh_user_secret( int $user_id, bool $enforce_current_user = true ) {
		// Return an error if the user cannot revoke the secret.
		if ( ! self::current_user_can( $user_id, $enforce_current_user ) ) {
			self::set_status( 401 );
			return new WP_Error( 'graphql-headless-login-cannot-refresh-secret', __( 'The Secret cannot be refreshed for this user.', 'wp-graphql-headless-login' ) );
		}

		// Issue a new secret, essentially 'unrevoking' it.
		$secret = self::issue_new_user_secret( $user_id, $enforce_current_user );

		return ! empty( $secret );
	}

	/**
	 * Gets the allowed domains for the token.
	 *
	 * @return string[]
	 */
	public static function get_token_allowed_domains(): array {
		$allowed_domains = [ get_bloginfo( 'url' ) ];

		/**
		 * Filter the allowed domains for the token.
		 * This is useful if you want to make your token valid over several domains.
		 *
		 * @param string[] $allowed_domains The allowed domains.
		 */
		return apply_filters( 'graphql_login_iss_allowed_domains', $allowed_domains );
	}

	/**
	 * Returns the user capability necessary for editing/viewing other user's authentication info.
	 */
	public static function get_auth_cap(): string {
		/**
		 * Filter the capability that is tied to editing/viewing user authentication info.
		 *
		 * @param string $capability The user capability. Defaults to `edit_users`.
		 */
		return apply_filters( 'graphql_login_edit_jwt_capability', 'edit_users' );
	}

	/**
	 * Creates the array used to generate the token.
	 *
	 * @param \WP_User $user The user object.
	 * @param bool     $enforce_current_user Whether to enforce the current user.
	 *
	 * @return array<string,mixed>|\WP_Error
	 */
	protected static function prepare_token( WP_User $user, bool $enforce_current_user = true ) {
		/**
		 * Bail if the current user has incorrect permissions.
		 */
		if ( ! self::current_user_can( $user->ID, $enforce_current_user, $enforce_current_user ) ) {
			self::set_status( 400 );

			return new WP_Error( 'graphql-headless-login-no-permissions', __( 'Users can only request tokens for themselves.', 'wp-graphql-headless-login' ) );
		}

		/**
		 * Determines the "not before" value for the user's token.
		 *
		 * @param int      $issued The timestamp of the authentication, used in the token.
		 * @param \WP_User $user   The authenticated user.
		 */
		$nbf = apply_filters( 'graphql_login_token_not_before_timestamp', self::get_issued_at(), $user );

		/**
		 * Determines the expiration time for the user's token.
		 *
		 * @param int      $issued The timestamp of the expiration, used in the token.
		 * @param \WP_User $user   The authenticated user.
		 */
		$expiration = apply_filters( 'graphql_login_token_expiration_timestamp', self::get_expiration(), $user );

		$token = [
			'iss'  => get_bloginfo( 'url' ),
			'iat'  => self::get_issued_at(),
			'nbf'  => $nbf,
			'exp'  => $expiration,
			'data' => [
				'user' => [
					'id' => $user->ID,
				],
			],
		];

		/**
		 * Filters the token before it is signed, allowing for individual systems to configure the token as needed.
		 *
		 * @param array    $token The token array that will be encoded.
		 * @param \WP_User $user The authenticated user.
		 */
		return apply_filters( 'graphql_login_token_before_sign', $token, $user );
	}

	/**
	 * Gets the signed JWT token.
	 *
	 * @param array<string,mixed> $token The token data.
	 * @param int                 $user_id The user ID.
	 */
	protected static function sign_token( array $token, int $user_id ): ?string {
		JWT::$leeway  = 60;
		$signed_token = JWT::encode( $token, self::get_secret_key(), 'HS256' );

		/**
		 * Filter the token before returning it, allowing for individual systems to override what's returned.
		 *
		 * For example, if the user should not be granted a token for whatever reason, a filter could have the token return null.
		 *
		 * @param string $token   The signed JWT token that will be returned
		 * @param int    $user_id The User the JWT is associated with
		 */
		$signed_token = apply_filters( 'graphql_login_signed_token', $signed_token, $user_id );

		// Return the token.
		return empty( $signed_token ) ? null : $signed_token;
	}

	/**
	 * Sets the GraphQL response status code.
	 *
	 * @param int $status_code The status code to set.
	 */
	protected static function set_status( int $status_code ): void {
		add_filter(
			'graphql_response_status_code',
			static fn () => $status_code
		);
	}

	/**
	 * Gets the time the token was issued.
	 */
	protected static function get_issued_at(): int {
		if ( ! isset( self::$issued_at ) ) {
			self::$issued_at = time();
		}

		return self::$issued_at;
	}

	/**
	 * Gets the time the token will expire.
	 */
	protected static function get_expiration(): int {
		if ( ! isset( self::$expiration ) ) {
			/**
			 * Filter the expiration time for the token.
			 * Defaults to 300 seconds
			 *
			 * @param int $validity The expiration time for the token.
			 */
			$validity = apply_filters( 'graphql_login_token_validity', 300 );

			self::$expiration = self::get_issued_at() + $validity;
		}

		return self::$expiration;
	}

	/**
	 * Checks whether the user secret has been revoked.
	 *
	 * @param int $user_id The user ID.
	 */
	public static function is_user_secret_revoked( int $user_id ): bool {
		return User::get_is_secret_revoked( $user_id );
	}

	/**
	 * Issues a new secret for the user.
	 *
	 * @param int  $user_id The user ID.
	 * @param bool $enforce_current_user Whether to enforce the current user. Defaults to true.
	 *
	 * @return string|\WP_Error
	 */
	public static function issue_new_user_secret( int $user_id, bool $enforce_current_user = true ) {
		// Bail if the current user has incorrect permissions.
		if ( ! self::current_user_can( $user_id, $enforce_current_user ) ) {
			self::set_status( 400 );

			return new WP_Error( 'graphql-headless-login-no-permissions', __( 'Users can only issue new secrets for themselves.', 'wp-graphql-headless-login' ) );
		}

		$secret = uniqid( 'graphql_login_secret_', true );

		// Update the user meta.
		User::set_secret( $user_id, $secret );
		User::set_is_secret_revoked( $user_id, false );

		return $secret;
	}

	/**
	 * Validates the JWT Token.
	 *
	 * @param string $token The token to validate. If null, the token will be retrieved from the request.
	 * @param bool   $is_refresh_token Whether the token is a refresh token.
	 *
	 * @return object|\WP_Error|null
	 */
	public static function validate_token( ?string $token = null, bool $is_refresh_token = false ) {
		// If no token provided, grab it from the auth header.
		if ( empty( $token ) && ! $is_refresh_token ) {
			$auth_header = self::get_auth_header();

			// Bail early if no header.
			if ( empty( $auth_header ) ) {
				return null;
			}

			// Grab the token from the header, verifying the format.
			$header_token = sscanf( $auth_header, 'Bearer %s' );
			// Bail if no token set in header.
			if ( empty( $header_token ) ) {
				return null;
			}

			list( $token ) = $header_token;
		}

		/**
		 * If there's no secret key, throw an error as there needs to be a secret key for Auth to work properly
		 */
		$secret_key = self::get_secret_key();

		if ( empty( $secret_key ) ) {
			self::set_status( 403 );
			return new WP_Error( 'invalid-secret-key', __( 'The JWT secret key is not set.', 'wp-graphql-headless-login' ) );
		}

		// Decode the token.
		JWT::$leeway = 60;

		try {
			$token = empty( $token ) ? null : JWT::decode( sanitize_text_field( $token ), new Key( $secret_key, 'HS256' ) );
		} catch ( \Throwable $exception ) {
			/** @var \Exception $exception */
			$token = new WP_Error( 'invalid-secret-key', $exception->getMessage() );
		}

		// Bail early if the token is invalid.
		if ( empty( $token ) || is_wp_error( $token ) ) {
			if ( is_wp_error( $token ) ) {
				self::set_status( 403 );
			}

			return $token;
		}

		$allowed_domains = self::get_token_allowed_domains();

		// Validate the iss.
		if ( ! in_array( $token->iss, $allowed_domains, true ) ) {
			self::set_status( 403 );
			return new WP_Error( 'invalid-jwt', __( 'The iss do not match with this server.', 'wp-graphql-headless-login' ) );
		}

		// Validate the user Id.
		if ( empty( $token->data->user->id ) ) {
			self::set_status( 401 );
			return new WP_Error( 'invalid-jwt', __( 'User ID not found in the token.', 'wp-graphql-headless-login' ) );
		}

		// Validate the user secret.
		if ( ! $is_refresh_token && isset( $token->data->user->user_secret ) ) {
			self::set_status( 401 );
			return new WP_Error( 'invalid-jwt', __( 'Refresh token cannot be used as an auth token.', 'wp-graphql-headless-login' ) );
		}

		if ( $is_refresh_token ) {
			// The user secret isnt part of the token.
			if ( empty( $token->data->user->user_secret ) ) {
				self::set_status( 401 );
				return new WP_Error( 'invalid-jwt', __( 'User secret not found in the token.', 'wp-graphql-headless-login' ) );
			}
			// The user secret has been revoked.
			if ( self::is_user_secret_revoked( $token->data->user->id ) ) {
				self::set_status( 401 );
				return new WP_Error( 'invalid-jwt', __( 'User secret is revoked.', 'wp-graphql-headless-login' ) );
			}
			// the secret in the token doesnt match the user secret.
			if ( User::get_secret( $token->data->user->id ) !== $token->data->user->user_secret ) {
				self::set_status( 401 );
				return new WP_Error( 'invalid-jwt', __( 'User secret does not match.', 'wp-graphql-headless-login' ) );
			}
		}

		return $token;
	}

	/**
	 * Gets the HTTP Authorization header.
	 */
	public static function get_auth_header(): string {
		/**
		 * Looking for the HTTP_AUTHORIZATION header.
		 */
		$auth_header = isset( $_SERVER['HTTP_AUTHORIZATION'] ) ? sanitize_text_field( $_SERVER['HTTP_AUTHORIZATION'] ) : false;

		if ( false === $auth_header ) {
			/**
			 * Looking for the REDIRECT_HTTP_AUTHORIZATION header.
			 */
			$auth_header = isset( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) ? sanitize_text_field( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) : false;
		}

		// Ensure the auth header is a string.
		$auth_header = ! empty( $auth_header ) && is_string( $auth_header ) ? $auth_header : '';

		/**
		 * Return the auth header, passed through a filter
		 *
		 * @param string $auth_header The header used to authenticate a user's HTTP request
		 */
		return apply_filters( 'graphql_login_auth_header', $auth_header );
	}

	/**
	 * Gets the Refrresh-Authorization-header header.
	 */
	public static function get_refresh_header(): string {
		$refresh_header = isset( $_SERVER['HTTP_REFRESH_AUTHORIZATION'] ) ? sanitize_text_field( $_SERVER['HTTP_REFRESH_AUTHORIZATION'] ) : '';

		/**
		 * Filters the refresh header.
		 *
		 * @param string $refresh_header The refresh header.
		 */
		return apply_filters( 'graphql_login_refresh_header', $refresh_header );
	}

	/**
	 * Whether the current user can manipluate auth date for the provided user Id.
	 *
	 * @param int  $user_id The user who's data is being acted upon.
	 * @param bool $enforce_current_user Whether to enforce the current user. Defaults to true.
	 * @param bool $enforce_auth_cap     Whether to enforce the auth cap. Defaults to true.
	 */
	public static function current_user_can( int $user_id, bool $enforce_current_user = true, bool $enforce_auth_cap = true ): bool {
		// Allow if we're not enforcing anything.
		if ( ! $enforce_current_user && ! $enforce_auth_cap ) {
			return true;
		}

		// If the user is the current one, its always allowed.
		if ( Utils::is_current_user( $user_id ) ) {
			return true;
		}

		// Allow if the current user has the correct permissions.
		if ( ! $enforce_current_user && current_user_can( self::get_auth_cap() ) ) { // phpcs:ignore WordPress.WP.Capabilities.Undetermined
			return true;
		}

		return false;
	}
}
