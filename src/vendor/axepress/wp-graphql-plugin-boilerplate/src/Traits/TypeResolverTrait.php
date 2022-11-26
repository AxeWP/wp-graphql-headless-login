<?php
/**
 * Trait for getting possible resolve types.
 *
 * @package AxeWP\GraphQL\Traits
 *
 * @license GPL-3.0-or-later
 * Modified by AxePress Development using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace WPGraphQL\Login\Vendor\AxeWP\GraphQL\Traits;

use Closure;
use Error;

if ( ! trait_exists( '\WPGraphQL\Login\Vendor\AxeWP\GraphQL\Traits\TypeResolverTrait' ) ) {

	/**
	 * Trait - TypeResolverTrait
	 *
	 * @property ?\WPGraphQL\Registry\TypeRegistry $type_registry The WPGraphQL TypeRegistry instance.
	 */
	trait TypeResolverTrait {

		/**
		 * The function used to resolve the Interface type in the `resolveType` callback.
		 *
		 * @throws Error If $type_registry is not set.
		 */
		protected static function get_type_resolver() : Closure {
			/**
			 * @param mixed       $value The value from the resolver of the parent field.
			 */
			return static function( $value ) {
				if ( ! static::$type_registry instanceof \WPGraphQL\Registry\TypeRegistry ) {
					throw new Error(
						sprintf(
						// translators: function name.
							__( 'Incorrect usage of %s. This method may only be called after self::$type_registry is set.', 'wp-graphql-plugin-name' ),
							__FUNCTION__
						)
					);
				}

				$type_name = static::get_resolved_type_name( $value );

				if ( empty( $type_name ) ) {
					throw new Error(
					// translators: the GraphQL interface type name.
						sprintf( __( 'The value passed to %s failed to resolve to a valid GraphQL type', 'wp-graphql-plugin-name' ), static::class )
					);
				}

				return static::$type_registry->get_type( $type_name );
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
