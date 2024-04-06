<?php
/**
 * Abstract class to make it easy to register Types to WPGraphQL.
 *
 * @package AxeWP\GraphQL\Abstracts
 *
 * @license GPL-3.0-or-later
 * Modified by AxePress Development using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare( strict_types=1 );

namespace WPGraphQL\Login\Vendor\AxeWP\GraphQL\Abstracts;

use WPGraphQL\Login\Vendor\AxeWP\GraphQL\Interfaces\GraphQLType;
use WPGraphQL\Login\Vendor\AxeWP\GraphQL\Interfaces\Registrable;
use WPGraphQL\Login\Vendor\AxeWP\GraphQL\Traits\TypeNameTrait;

if ( ! class_exists( '\WPGraphQL\Login\Vendor\AxeWP\GraphQL\Abstracts\Type' ) ) {

	/**
	 * Class - Type
	 */
	abstract class Type implements GraphQLType, Registrable {
		use TypeNameTrait;

		/**
		 * {@inheritDoc}
		 */
		public static function init(): void {
			add_action( 'graphql_register_types', [ static::class, 'register' ] );
		}

		/**
		 * Defines the GraphQL type name registered in WPGraphQL.
		 */
		abstract protected static function type_name(): string;

		/**
		 * Gets the GraphQL type description.
		 */
		abstract public static function get_description(): string;

		/**
		 * Gets the $config array used to register the type to WPGraphQL.
		 *
		 * @return array<string,mixed>
		 */
		protected static function get_type_config(): array {
			return [
				'description'     => static::get_description(),
				'eagerlyLoadType' => static::should_load_eagerly(),
			];
		}

		/**
		 * Whether the type should be loaded eagerly by WPGraphQL. Defaults to false.
		 *
		 * Eager load should only be necessary for types that are not referenced directly (e.g. in Unions, Interfaces ).
		 */
		public static function should_load_eagerly(): bool {
			return false;
		}
	}
}
