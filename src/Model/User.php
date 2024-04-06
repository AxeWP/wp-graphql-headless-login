<?php
/**
 * Extends the User Model with authentication data.
 *
 * @package WPGraphQL\Login\Model
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Model;

use WPGraphQL\Login\Auth\TokenManager;
use WPGraphQL\Login\Auth\User as AuthUser;

/**
 * Class - User
 *
 * @property ?string $authToken
 * @property ?int    $authTokenExpiration
 * @property bool    $isUserSecretRevoked
 * @property ?string $refreshToken
 * @property ?int    $refreshTokenExpiration
 * @property ?string $userSecret
 * @property ?array  $linkedIdentities
 */
class User {
	/**
	 * Extends the User Model with authentication data.
	 *
	 * @param array<string,mixed> $fields The fields to extend.
	 * @param string              $model_name The model name.
	 * @param \WP_User|mixed      $data The user object.
	 *
	 * @return array<string,mixed>
	 */
	public static function get_fields( array $fields, string $model_name, $data ): array {
		if ( 'UserObject' !== $model_name ) {
			return $fields;
		}

		/** @var \WP_User $data */
		$fields['authToken']              = static fn () => TokenManager::get_auth_token( $data, true );
		$fields['authTokenExpiration']    = static fn () => AuthUser::get_auth_token_expiration( $data->ID );
		$fields['isUserSecretRevoked']    = static fn () => TokenManager::is_user_secret_revoked( $data->ID );
		$fields['refreshToken']           = static fn () => TokenManager::get_refresh_token( $data, true );
		$fields['refreshTokenExpiration'] = static fn () => AuthUser::get_refresh_token_expiration( $data->ID );
		$fields['userSecret']             = static fn () => TokenManager::get_user_secret( $data->ID, true );

		$fields['linkedIdentities'] = static function () use ( $data ): ?array {
			$user_identies = AuthUser::get_user_identities( $data->ID );

			return array_map(
				static function ( $identity, $provider ) {
					return [
						'provider' => $provider,
						'id'       => $identity,
					];
				},
				$user_identies,
				array_keys( $user_identies )
			) ?: null;
		};

		return $fields;
	}
}
