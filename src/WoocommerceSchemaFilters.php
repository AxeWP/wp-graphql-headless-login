<?php
/**
 * Adds filters that modify WooGraphQL schema.
 *
 * @package WPGraphQL\Login
 */

namespace WPGraphQL\Login;

use WPGraphQL\Login\Type\WPObject\AuthenticationData;
use WPGraphQL\Login\Vendor\AxeWP\GraphQL\Interfaces\Registrable;

/**
 * Class - WoocommerceSchemaFilters
 */
class WoocommerceSchemaFilters implements Registrable {
	/**
	 * {@inheritDoc}
	 */
	public static function init(): void {
		// Bail if WPGraphQL for Woocommerce doesnt exist.
		if ( ! defined( 'WPGRAPHQL_WOOCOMMERCE_VERSION' ) ) {
			return;
		}

		add_filter( 'graphql_login_user_types', [ self::class, 'add_customer_to_user_types' ] );
		add_action( 'graphql_register_types', [ self::class, 'add_fields' ] );
	}

	/**
	 * Adds the Customer object to the list of 'User' types that get AuthenticationData.
	 *
	 * @param string[] $types The GraphQL type names.
	 *
	 * @return string[]
	 */
	public static function add_customer_to_user_types( array $types ): array {
		$types[] = 'Customer';

		return $types;
	}

	/**
	 * Adds WooGraphQL fields to the plugin GraphQL types.
	 */
	public static function add_fields(): void {
		// Register session token to Authentication data.
		register_graphql_field(
			AuthenticationData::get_type_name(),
			'wooSessionToken',
			[
				'type'        => 'String',
				'description' => __( 'A JWT token used to identify the current WooCommerce session', 'wp-graphql-headless-login' ),
				'resolve'     => static function ( $user ) {
					if ( ! function_exists( 'WC' ) ) {
						return null;
					}

					if ( get_current_user_id() !== $user->databaseId && 'guest' !== $user->id ) {
						return null;
					}

					/** @var \WPGraphQL\WooCommerce\Utils\QL_Session_Handler $session */
					$session = \WC()->session;

					/** \WooCommerce::$session */
					return apply_filters( 'graphql_customer_session_token', $session->build_token() ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
				},
			]
		);

		// Register the customer and session token to the Login payloads.
		register_graphql_fields(
			'LoginPayload',
			[
				'customer'        => [
					'type'        => 'Customer',
					'description' => __( 'The customer object for the logged in user', 'wp-graphql-headless-login' ),
					'resolve'     => static function ( $payload ) {
						$user_id = isset( $payload['user']->ID ) ? $payload['user']->ID : null;

						if ( ! $user_id ) {
							return null;
						}

						return new \WPGraphQL\WooCommerce\Model\Customer( $user_id );
					},
				],
				'wooSessionToken' => [
					'type'        => 'String',
					'description' => __( 'A JWT token used to identify the current WooCommerce session', 'wp-graphql-headless-login' ),
					'resolve'     => static function () {
						if ( ! function_exists( 'WC' ) ) {
							return null;
						}

						/** @var \WPGraphQL\WooCommerce\Utils\QL_Session_Handler $session */
						$session = \WC()->session;

						/** \WooCommerce::$session */
						return apply_filters( 'graphql_customer_session_token', $session->build_token() ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
					},
				],
			]
		);
	}
}
