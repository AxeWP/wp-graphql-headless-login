<?php
/**
 * Trait for getting possible resolve types.
 *
 * @package AxeWP\GraphQL\Traits
 *
 * @license GPL-3.0-or-later
 * Modified by AxePress Development using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare( strict_types=1 );

namespace WPGraphQL\Login\Vendor\AxeWP\GraphQL\Traits;

use Closure;
use Error;
use WPGraphQL;

if ( ! trait_exists( '\WPGraphQL\Login\Vendor\AxeWP\GraphQL\Traits\TypeResolverTrait' ) ) {

	/**
	 * Trait - TypeResolverTrait
	 */
	trait TypeResolverTrait {
		/**
		 * The function used to resolve the Interface type in the `resolveType` callback.
		 */
		protected static function get_type_resolver(): Closure {
			/**
			 * @param mixed       $value The value from the resolver of the parent field.
			 */
			return static function ( $value ) {
				$type_name = static::get_resolved_type_name( $value );

				if ( empty( $type_name ) ) {
					throw new Error(
					// translators: the GraphQL interface type name.
						sprintf( esc_html__( 'The value passed to %s failed to resolve to a valid GraphQL type', 'wp-graphql-plugin-name' ), static::class )
					);
				}

				$type_registry = WPGraphQL::get_type_registry();

				return $type_registry->get_type( $type_name );
			};
		}

		/**
		 * Gets the name of the GraphQL type to that the interface/union resolves to.
		 *
		 * @param mixed $value The value from the resolver of the parent field.
		 */
		abstract public static function get_resolved_type_name( $value ): ?string;
	}
}
