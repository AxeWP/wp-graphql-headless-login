<?php
/**
 * Methods handling the WordPress User.
 *
 * @package WPGraphQL\Login\Auth
 * @since 0.0.1
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Auth;

use WP_User_Query;

/**
 * Class - User
 */
class User {
	/**
	 * The prefix for the WP_User meta identity key.
	 *
	 * @var string
	 */
	public static string $identity_key_prefix = 'wp-graphql-headless-login-subject-identity';

	/**
	 * Gets the WP User by a given user meta key and value.
	 *
	 * @param string $key   The meta key to check.
	 * @param string $value The value to match.
	 *
	 * @return \WP_User|false
	 */
	public static function get_user_by( string $key, string $value ) {
		if ( in_array( $key, [ 'id', 'ID', 'slug', 'email', 'login' ], true ) ) {
			return get_user_by( $key, $value );
		}

		$user_query = new WP_User_Query(
			[
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'meta_query' => [
					[
						'key'   => $key,
						'value' => $value,
					],
				],
			],
		);

		// If we found existing users, grab the first one returned.
		if ( $user_query->get_total() > 0 ) {
			$users = $user_query->get_results();
			return $users[0];
		}

		return false;
	}

	/**
	 * Gets the WP User by the ResourceOwner ID returned from the provider.
	 *
	 * @param string $provider the Provider slug.
	 * @param string $subject_identity The ResourceOwner ID.
	 *
	 * @return \WP_User|false
	 */
	public static function get_user_by_identity( string $provider, string $subject_identity ) {
		return self::get_user_by( self::get_identity_meta_key( $provider ), $subject_identity );
	}

	/**
	 * Links the WP User to the ResourceOwner.
	 *
	 * @param int    $user_id The WP User ID.
	 * @param string $provider The Provider slug.
	 * @param string $subject_identity The ResourceOwner ID.
	 *
	 * @return \WP_User|false
	 */
	public static function link_user_identity( int $user_id, string $provider, string $subject_identity ) {
		update_user_meta( $user_id, self::get_identity_meta_key( $provider ), $subject_identity );

		return get_user_by( 'id', $user_id );
	}

	/**
	 * Unlinks the WP User from the ResourceOwner.
	 *
	 * @param int    $user_id The WP User ID.
	 * @param string $provider The Provider slug.
	 */
	public static function unlink_user_identity( int $user_id, string $provider ): bool {
		return delete_user_meta( $user_id, self::get_identity_meta_key( $provider ) );
	}

	/**
	 * If the proper options are enabled, tries first to link an existing WP_User to the ResourceOwner, then creates a new WP_User.
	 *
	 * @param \WPGraphQL\Login\Auth\Client $client The Auth client.
	 * @param array<string,mixed>          $user_data The user data.
	 *
	 * @return \WP_User|\WP_Error|false
	 */
	public static function maybe_create_user( Client $client, array $user_data ) {
		$config = $client->get_config();

		if ( ! empty( $config['loginOptions']['linkExistingUsers'] ) ) {
			$user_id = email_exists( $user_data['user_email'] );

			if ( ! empty( $user_id ) ) {
				return self::link_user_identity( $user_id, $client->get_provider_slug(), $user_data['subject_identity'] );
			}
		}

		if ( empty( $config['loginOptions']['createUserIfNoneExists'] ) ) {
			return false;
		}

		// Copy the username for incrementing.
		$_username = $user_data['user_login'];
		// If the username already exists, increment it.
		$count = 1;
		while ( username_exists( $user_data['user_login'] ) ) {
			++$count;
			$user_data['user_login'] = $_username . $count;
		}

		// Set password.
		$user_data['user_pass'] = wp_generate_password( 32, true, true );

		// Create the user.
		$user_id = wp_insert_user( $user_data );

		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		return self::link_user_identity( $user_id, $client->get_provider_slug(), $user_data['subject_identity'] );
	}

	/**
	 * Gets all the identities for a user, keyed to the provider slug.
	 *
	 * @param int $user_id The WP User ID.
	 *
	 * @return array<string,string>
	 */
	public static function get_user_identities( int $user_id ): array {
		$providers = ProviderRegistry::get_instance()->get_providers();

		$identities = [];
		foreach ( array_keys( $providers ) as $provider ) {
			$identity = get_user_meta( $user_id, self::get_identity_meta_key( $provider ), true );
			if ( ! empty( $identity ) ) {
				$identities[ $provider ] = $identity;
			}
		}

		return $identities;
	}

	/**
	 * Gets the identity meta key for a provider.
	 *
	 * @param string $provider The provider slug.
	 */
	public static function get_identity_meta_key( string $provider ): string {
		return self::$identity_key_prefix . '-' . $provider;
	}

	/**
	 * Gets the auth token expiration for the user.
	 *
	 * @param int $user_id The WP User ID.
	 */
	public static function get_auth_token_expiration( int $user_id ): ?int {
		$value = get_user_meta( $user_id, 'graphql_login_token_expiration', true );

		return ! empty( $value ) ? absint( $value ) : null;
	}

	/**
	 * Get the refresh token expiration for the user.
	 *
	 * @param int $user_id The WP User ID.
	 */
	public static function get_refresh_token_expiration( int $user_id ): ?int {
		$value = get_user_meta( $user_id, 'graphql_login_refresh_token_expiration', true );

		return ! empty( $value ) ? absint( $value ) : null;
	}

	/**
	 * Gets the user secret.
	 *
	 * @param int $user_id The WP User ID.
	 */
	public static function get_secret( int $user_id ): ?string {
		if ( self::get_is_secret_revoked( $user_id ) ) {
			return null;
		}

		$value = get_user_meta( $user_id, 'graphql_login_secret', true );

		return ! empty( $value ) ? (string) $value : null;
	}

	/**
	 * Gets whether the user secret is revoked.
	 *
	 * @param int $user_id The WP User ID.
	 */
	public static function get_is_secret_revoked( int $user_id ): bool {
		$value = get_user_meta( $user_id, 'graphql_login_secret_revoked', true );

		return ! empty( $value );
	}

	/**
	 * Sets the auth token expiration date for the user.
	 *
	 * @param int $user_id The WP User ID.
	 * @param int $expiration The expiration timestamp.
	 */
	public static function set_auth_token_expiration( int $user_id, int $expiration ): bool {
		return (bool) update_user_meta( $user_id, 'graphql_login_token_expiration', $expiration );
	}

	/**
	 * Sets the refresh token expiration date for the user.
	 *
	 * @param int $user_id The WP User ID.
	 * @param int $expiration The expiration timestamp.
	 */
	public static function set_refresh_token_expiration( int $user_id, int $expiration ): bool {
		return (bool) update_user_meta( $user_id, 'graphql_login_refresh_token_expiration', $expiration );
	}

	/**
	 * Sets the user secret.
	 *
	 * @param int    $user_id The WP User ID.
	 * @param string $secret The secret.
	 */
	public static function set_secret( int $user_id, string $secret ): bool {
		return (bool) update_user_meta( $user_id, 'graphql_login_secret', $secret );
	}

	/**
	 * Revokes the user secret.
	 *
	 * @param int  $user_id The WP User ID.
	 * @param bool $is_revoked Whether the secret is revoked.
	 */
	public static function set_is_secret_revoked( int $user_id, bool $is_revoked ): bool {
		return (bool) update_user_meta( $user_id, 'graphql_login_secret_revoked', $is_revoked );
	}
}
