<?php
/**
 * Handles authorization and authentication.
 *
 * @package WPGraphQL\Login\Auth
 * @since 0.0.1
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Auth;

use GraphQL\Error\UserError;
use WPGraphQL\Login\Auth\Client;
use WPGraphQL\Login\Auth\ProviderConfig\Password;
use WPGraphQL\Utils\Utils;
use WP_Error;

/**
 * Class - Auth
 */
class Auth {
	/**
	 * Gets the Client instance for the provider.
	 *
	 * @param string $provider The provider slug.
	 *
	 * @throws \GraphQL\Error\UserError If the client is invalid.
	 */
	public static function get_client( string $provider ): Client {
		$client = new Client( $provider );

		// Ensure the client is valid before returning.
		self::validate_client( $client );

		return $client;
	}

	/**
	 * Validates the Authentication provider's response and logs in the user.
	 *
	 * @param array<string,mixed> $input The mutation input.
	 *
	 * @return array<string,mixed> The user data.
	 * @throws \GraphQL\Error\UserError If the user cannot be created.
	 */
	public static function login( array $input ): array {
		// If the user is already logged in, throw an error.
		if ( is_user_logged_in() ) {
			throw new UserError( esc_html__( 'You are already logged in.', 'wp-graphql-headless-login' ) );
		}

		// Get the client from the provider config.
		$client = self::get_client( $input['provider'] );

		// Authenticate and get the user data.
		$user_data = $client->authenticate_and_get_user_data( $input );

		if ( $user_data instanceof \WP_Error ) {
			throw new UserError( esc_html( $user_data->get_error_message() ) );
		}

		$user = ! empty( $user_data ) ? $client->get_user_from_data( $user_data ) : false;

		// If there was no user, maybe create one.
		if ( empty( $user ) ) {
			$user = $client->maybe_create_user( $user_data );
		}

		if ( $user instanceof \WP_Error ) {
			throw new UserError( esc_html( $user->get_error_message() ) );
		}

		if ( ! $user instanceof \WP_User ) {
			throw new UserError( esc_html__( 'The user could not be logged in.', 'wp-graphql-headless-login' ) );
		}

		// Login and generate the tokens.
		wp_set_current_user( $user->ID );

		// Set the auth cookie if the provider is configured to use it.
		$config = $client->get_config();
		if ( ! empty( $config['loginOptions']['useAuthenticationCookie'] ) ) {
			wp_set_auth_cookie( $user->ID, false );
		}

		// Trigger the login action.
		do_action( 'wp_login', $user->user_login, $user ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

		$payload = [
			'authToken'              => TokenManager::get_auth_token( $user ),
			'authTokenExpiration'    => User::get_auth_token_expiration( $user->ID ),
			'refreshToken'           => TokenManager::get_refresh_token( $user ),
			'refreshTokenExpiration' => User::get_refresh_token_expiration( $user->ID ),
			'user'                   => $user,
			'id'                     => $user->ID,
		];

		/**
		 * Filters the Login mutation payload before returning.
		 *
		 * @param array                        $payload   The payload.
		 * @param \WP_User                     $user      The user.
		 * @param array|\WP_User|false         $user_data The user data from the Provider.
		 * @param \WPGraphQL\Login\Auth\Client $client    The client instance.
		 */
		$payload = apply_filters( 'graphql_login_payload', $payload, $user, $user_data, $client );

		/**
		 * Fires after the user is successfully logged in.
		 *
		 * @param array                              $payload   The payload.
		 * @param array<string,mixed>|\WP_User|false $user_data The user data from the Provider.
		 * @param \WPGraphQL\Login\Auth\Client       $client    The client instance.
		 */
		do_action( 'graphql_login_after_successful_login', $payload, $user_data, $client );

		return $payload ?: [];
	}

	/**
	 * Validates the Authentication provider's response and links it to an existing user account.
	 *
	 * @param array<string,mixed> $input The mutation input.
	 *
	 * @return array<string,mixed> The user data.
	 * @throws \GraphQL\Error\UserError If the user cannot be linked.
	 */
	public static function link_user_identity( array $input ): array {
		if ( Password::get_slug() === $input['provider'] ) {
			throw new UserError( esc_html__( 'You cannot link two identities from the same WordPress site. Please use a different `provider`.', 'wp-graphql-headless-login' ) );
		}

		if ( ! is_user_logged_in() ) {
			throw new UserError( esc_html__( 'You must be logged in to link your identity.', 'wp-graphql-headless-login' ) );
		}

		$user_id  = Utils::get_database_id_from_id( $input['userId'] );
		$user_obj = ! empty( $user_id ) ? get_user_by( 'ID', $user_id ) : false;

		/**
		 * Only allow the currently signed in user to link their identity.
		 */
		if ( empty( $user_obj ) || get_current_user_id() !== $user_obj->ID ) {
			throw new UserError( esc_html__( 'You must be logged in as the user to link your identity.', 'wp-graphql-headless-login' ) );
		}

		// Get the client from the provider config.
		$client = self::get_client( $input['provider'] );

		// Try to authenticate the user.
		$user_data = $client->authenticate_and_get_user_data( $input );

		if ( $user_data instanceof \WP_Error ) {
			throw new UserError( esc_html( $user_data->get_error_message() ) );
		}

		if ( empty( $user_data ) || ! is_array( $user_data ) ) {
			throw new UserError( esc_html__( 'Unable to get user data.', 'wp-graphql-headless-login' ) );
		}

		$user = $client->get_user_from_data( $user_data );

		if ( false !== $user ) {
			if ( $user->ID === $user_obj->ID ) {
				throw new UserError( esc_html__( 'This identity is already linked to your account.', 'wp-graphql-headless-login' ) );
			}
			throw new UserError( esc_html__( 'This identity is already linked to another account.', 'wp-graphql-headless-login' ) );
		}

		$linked_user = User::link_user_identity( $user_obj->ID, $client->get_provider_slug(), $user_data['subject_identity'] );

		/**
		 * Fires when linking a user identity.
		 *
		 * @param \WP_User|false $linked_user The linked user.
		 * @param array         $user_data   The user data from the Provider.
		 * @param \WPGraphQL\Login\Auth\Client $client The client instance.
		 */
		do_action( 'graphql_login_link_user_identity', $linked_user, $user_data, $client );

		return [
			'success' => (bool) $linked_user,
			'user'    => false === $linked_user ? null : $linked_user,
		];
	}

	/**
	 * Validates the client instance.
	 *
	 * @param mixed $client The client instance.
	 *
	 * @throws \GraphQL\Error\UserError If the client is invalid.
	 */
	private static function validate_client( $client ): void {
		if ( $client instanceof WP_Error ) {
			throw new UserError( esc_html( $client->get_error_message() ) );
		}

		if ( ! $client instanceof Client ) {
			throw new UserError( esc_html__( 'Invalid Authentication client.', 'wp-graphql-headless-login' ) );
		}

		/**
		 * Fires when validating the client instance.
		 */
		do_action( 'graphql_login_validate_client', $client );
	}
}
