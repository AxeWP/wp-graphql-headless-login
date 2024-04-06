<?php
/**
 * The Headless Login Authentication Data GraphQL object.
 *
 * @package WPGraphQL\Login\Type\WPObject
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Type\WPObject;

use WPGraphQL\Login\Vendor\AxeWP\GraphQL\Abstracts\ObjectType;
use WPGraphQL\Model\User;

/**
 * Class - AuthenticationData
 */
class AuthenticationData extends ObjectType {
	/**
	 * {@inheritDoc}
	 */
	public static function register(): void {
		parent::register();

		/**
		 * Filters the GraphQL 'user' types which should have 'AuthenticationData' added to them.
		 *
		 * @param string[] $type_names The names of the GraphQL 'user' types. Defaults to 'User'
		 */
		$user_types = apply_filters( 'graphql_login_user_types', [ 'User' ] );

		foreach ( $user_types as $type ) {
			register_graphql_field(
				$type,
				'auth',
				[
					'type'        => self::get_type_name(),
					'description' => __( 'Headless Login authentication data.', 'wp-graphql-headless-login' ),
					'resolve'     => static function ( $source ) {
						if ( ! $source instanceof User && isset( $source->ID ) ) {
							$user = get_user_by( 'ID', $source->ID );

							if ( $user instanceof \WP_User ) {
								return new User( $user );
							}
						}
						return $source;
					},
				]
			);
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public static function type_name(): string {
		return 'AuthenticationData';
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_description(): string {
		return __( 'The Headless Login authentication data.', 'wp-graphql-headless-login' );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_fields(): array {
		return [
			'authToken'              => [
				'type'        => 'String',
				'description' => __( 'A new authentication token to use in future requests.', 'wp-graphql-headless-login' ),
			],
			'authTokenExpiration'    => [
				'type'        => 'String',
				'description' => __( 'The authentication token expiration timestamp.', 'wp-graphql-headless-login' ),
			],
			'isUserSecretRevoked'    => [
				'type'        => 'Boolean',
				'description' => __( 'Whether the user secret has been revoked.', 'wp-graphql-headless-login' ),
			],
			'linkedIdentities'       => [
				'type'        => [ 'list_of' => LinkedIdentity::get_type_name() ],
				'description' => __( 'A list of linked identities from the Headless Login provider.', 'wp-graphql-headless-login' ),
			],
			'userSecret'             => [
				'type'        => 'String',
				'description' => __( 'The user secret.', 'wp-graphql-headless-login' ),
			],
			'refreshToken'           => [
				'type'        => 'String',
				'description' => __( 'A new refresh token to use in future requests.', 'wp-graphql-headless-login' ),
			],
			'refreshTokenExpiration' => [
				'type'        => 'String',
				'description' => __( 'The refresh token expiration timestamp.', 'wp-graphql-headless-login' ),
			],
		];
	}
}
