<?php
/**
 * Adds filters that modify WooGraphQL schema.
 *
 * @package WPGraphQL\Login
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login;

/**
 * Class - WoocommerceSchemaFilters
 */
class WoocommerceSchemaFilters {
	/**
	 * Registers the filters that adapt the WPGraphQL for WooCommerce schema.
	 */
	public static function init(): void {
		// Bail if WPGraphQL for Woocommerce doesnt exist or is too old.
		if ( ! defined( 'WPGRAPHQL_WOOCOMMERCE_VERSION' ) || version_compare( WPGRAPHQL_WOOCOMMERCE_VERSION, '1.0.0', '<' ) ) {
			return;
		}

		add_filter( 'graphql_login_user_types', [ self::class, 'add_customer_to_user_types' ] );
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
}
