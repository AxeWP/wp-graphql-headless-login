<?php
/**
 * Trait for getting Type Names.
 *
 * @package AxeWP\GraphQL\Traits
 *
 * @license GPL-3.0-or-later
 * Modified by AxePress Development using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare( strict_types=1 );

namespace WPGraphQL\Login\Vendor\AxeWP\GraphQL\Traits;

use WPGraphQL\Login\Vendor\AxeWP\GraphQL\Helper\Helper;
use Exception;

if ( ! trait_exists( '\WPGraphQL\Login\Vendor\AxeWP\GraphQL\Traits\TypeNameTrait' ) ) {

	/**
	 * Trait - TypeNameTrait
	 */
	trait TypeNameTrait {
		/**
		 * Gets the GraphQL type name.
		 *
		 * @throws \Exception When the implementing class has no type name.
		 */
		final public static function get_type_name(): string {
			if ( ! is_callable( [ static::class, 'type_name' ] ) ) {
				throw new Exception(
				// translators: the implementing class.
					sprintf( esc_html__( 'To use TypeNameTrait, a %s must implement a `type_name()` method.', 'wp-graphql-plugin-name' ), static::class )
				);
			}

			$type_name = static::type_name();

			$hook_prefix = Helper::hook_prefix();

			/**
			 * Filter the GraphQL type name.
			 *
			 * Useful for adding a namespace or preventing plugin conflicts.
			 *
			 * @param string $prefix the prefix for the type.
			 * @param string $type the GraphQL type name.
			 */
			return apply_filters( $hook_prefix . '_type_prefix', '', $type_name ) . $type_name; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		}
	}
}
