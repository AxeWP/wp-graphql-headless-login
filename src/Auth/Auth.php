<?php
/**
 * Handles authorization and authentication.
 *
 * @package WPGraphQL\Login\Auth
 * @since 0.0.1
 */

namespace WPGraphQL\Login\Auth;

use GraphQL\Error\UserError;
use WP_Error;
use WPGraphQL\Login\Auth\Client;
use WPGraphQL\Utils\Utils;

/**
 * Class - Auth
 */
class Auth {

	/**
	 * Gets the Client instance for the provider.
	 *
	 * @param string $provider The provider slug.
	 *
	 * @throws UserError If the client is invalid.
	 */
	public static function get_client( string $provider ) : Client {
		$client = new Client( $provider );

		// Ensure the client is valid before returning.
		self::validate_client( $client );

		return $client;
	}

	/**
	 * Validates the Authentication provider's response and logs in the user.
	 *
	 * @param array $input the mutation input.
	 *
	 * @throws UserError If the user cannot be created.
	 */
	public static function login( array $input ) : array {
		// Get the client from the provider config.
		$client = self::get_client( $input['provider'] );

		$user_data = $client->authenticate_and_get_user_data( $input );

		switch ( $client->get_provider_type() ) {
			case 'oauth2': // If the provider is OAuth2, we might need to create a new user.
				$user = User::get_user_by_identity( $client->get_provider_slug(), $user_data['subject_identity'] );

				// Maybe create the user.
				if ( false === $user ) {
					$user = User::maybe_create_user( $client, $user_data );
				}
				break;
			case 'saml':
				// @todo .
			default: // For custom types, we provide a filter to get the user.
				/**
				 * Filter to transform the user data returned from ProviderConfig::authenticate_and_get_user_data() to an instance of WP_User.
				 *
				 * @param WP_User|WP_Error|null $user The user.
				 * @param string                $provider_type The provider type.
				 * @param array                 $user_data The user data.
				 * @param Client                $client The client instance.
				 */
				$user = apply_filters( 'graphql_login_auth_get_user', null, $client->get_provider_type(), $user_data, $client );
		}

		if ( is_wp_error( $user ) ) {
			throw new UserError( $user->get_error_message() );
		}

		if ( ! $user instanceof \WP_User ) {
			throw new UserError( __( 'The user could not be logged in.', 'wp-graphql-headless-login' ) );
		}

		// Login and generate the tokens.
		wp_set_current_user( $user->ID );

		$payload = [
			'authToken'              => TokenManager::get_auth_token( $user ),
			'authTokenExpiration'    => User::get_auth_token_expiration( $user->ID ),
			'refreshToken'           => TokenManager::get_refresh_token( $user ),
			'refreshTokenExpiration' => User::get_refresh_token_expiration( $user->ID ),
			'user'                   => $user,
		];

		/**
		 * Filters the Login mutation payload before returning.
		 *
		 * @param array    $payload   The payload.
		 * @param \WP_User $user      The user.
		 * @param array    $user_data The user data.
		 * @param Client   $client    The client instance.
		 */
		$payload = apply_filters( 'graphql_login_payload', $payload, $user, $user_data, $client );

		/**
		 * Fires after the user is successfully logged in.
		 *
		 * @param array  $payload   The payload.
		 * @param array  $user_data The user data from the Provider.
		 * @param Client $client    The client instance.
		 */
		do_action( 'graphql_login_after_successful_login', $payload, $user_data, $client );

		return $payload ?: [];
	}

	/**
	 * Validates the Authentication provider's response and links it to an existing user account.
	 *
	 * @param array $input the mutation input.
	 *
	 * @throws UserError If the user cannot be linked.
	 */
	public static function link_user_identity( array $input ) : array {
		$user_id  = Utils::get_database_id_from_id( $input['userId'] );
		$user_obj = ! empty( $user_id ) ? get_user_by( 'ID', $user_id ) : false;

		/**
		 * Only allow the currently signed in user to link their identity.
		 */
		if ( ! is_user_logged_in() || empty( $user_obj ) || get_current_user_id() !== $user_obj->ID ) {
			throw new UserError( __( 'You must be logged in as the user to link your identity.', 'wp-graphql-headless-login' ) );
		}

		// Get the client from the provider config.
		$client = self::get_client( $input['provider'] );

		$user_data = $client->authenticate_and_get_user_data( $input );

		// Try to get the user.
		$user = User::get_user_by_identity( $client->get_provider_slug(), $user_data['subject_identity'] );

		if ( false !== $user ) {
			if ( $user->ID === $user_obj->ID ) {
				throw new UserError( __( 'This identity is already linked to your account.', 'wp-graphql-headless-login' ) );
			}
			throw new UserError( __( 'This identity is already linked to another account.', 'wp-graphql-headless-login' ) );
		}

		$linked_user = User::link_user_identity( $user_obj->ID, $client->get_provider_slug(), $user_data['subject_identity'] );

		/**
		 * Fires when linking a user identity.
		 *
		 * @param \WP_User|false $linked_user The linked user.
		 * @param array         $user_data   The user data from the Provider.
		 * @param Client        $client      The client instance.
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
	 * @throws UserError If the client is invalid.
	 */
	private static function validate_client( $client ) : void {
		if ( $client instanceof WP_Error ) {
			throw new UserError( $client->get_error_message() );
		}

		if ( ! $client instanceof Client ) {
			throw new UserError( __( 'Invalid Authentication client.', 'wp-graphql-headless-login' ) );
		}

		/**
		 * Fires when validating the client instance.
		 */
		do_action( 'graphql_login_validate_client', $client );
	}
}
