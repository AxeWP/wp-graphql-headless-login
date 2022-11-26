<?php
/**
 * Registers the LoginWithPassword mutation
 *
 * @package WPGraphQL\Login\Mutation
 * @since 0.0.1
 */

namespace WPGraphQL\Login\Mutation;

use GraphQL\Error\UserError;
use GraphQL\Type\Definition\ResolveInfo;
use WPGraphQL\AppContext;
use WPGraphQL\Login\Auth\TokenManager;
use WPGraphQL\Login\Auth\User;
use \WPGraphQL\Login\Vendor\AxeWP\GraphQL\Abstracts\MutationType;
/**
 * Class - LoginWithPassword
 */
class LoginWithPassword extends MutationType {

	/**
	 * {@inheritDoc}
	 */
	public static function type_name() : string {
		return 'LoginWithPassword';
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_input_fields() : array {
		return [
			'username' => [
				'type'        => [ 'non_null' => 'String' ],
				'description' => __( 'The username used for login. In some configurations, this may be the users email address.', 'wp-graphql-headless-login' ),
			],
			'password' => [
				'type'        => [ 'non_null' => 'String' ],
				'description' => __( 'The plain-text password for the user logging in.', 'wp-graphql-headless-login' ),
			],
		];
	}

	/**
	 * {@inheritDoc}
	 *
	 * The return object is the same as for OAuth2 Logins.
	 */
	public static function get_output_fields() : array {
		return Login::get_output_fields();
	}

	/**
	 * {@inheritDoc}
	 */
	public static function mutate_and_get_payload() : callable {
		return static function ( array $input, AppContext $context, ResolveInfo $info ): array {
			// If the user is already logged in, throw an error.
			if ( is_user_logged_in() ) {
				throw new UserError( __( 'You are already logged in.', 'wp-graphql-headless-login' ) );
			}
			/**
			 * Fires before the user is authenticated with a password.
			 */
			do_action( 'graphql_login_before_password_authenticate', $input );

			// Sanitize the input.
			$username = sanitize_user( $input['username'] );
			$password = trim( $input['password'] );

			$user = wp_authenticate( $username, $password );

			if ( is_wp_error( $user ) ) {
				throw new UserError( wp_strip_all_tags( $user->get_error_message() ) );
			}

			wp_set_current_user( $user->ID );

			$payload = [
				'authToken'              => TokenManager::get_auth_token( $user ),
				'authTokenExpiration'    => User::get_auth_token_expiration( $user->ID ),
				'refreshToken'           => TokenManager::get_refresh_token( $user ),
				'refreshTokenExpiration' => User::get_refresh_token_expiration( $user->ID ),
				'user'                   => $user,
			];

			$payload = apply_filters( 'graphql_login_payload', $payload, $user );

			/**
			 * Fires after the user is authenticated with a password.
			 *
			 * @param array $payload The payload.
			 * @param \WP_User $user The user.
			 */
			do_action( 'graphql_login_after_successful_password_login', $payload, $user );

			return $payload ?: [];
		};
	}
}
