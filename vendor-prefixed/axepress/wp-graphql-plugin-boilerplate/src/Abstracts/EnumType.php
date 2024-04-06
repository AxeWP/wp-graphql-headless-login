<?php
/**
 * Abstract class to make it easy to register Enum types to WPGraphQL.
 *
 * @package AxeWP\GraphQL\Abstracts
 *
 * @license GPL-3.0-or-later
 * Modified by AxePress Development using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare( strict_types=1 );

namespace WPGraphQL\Login\Vendor\AxeWP\GraphQL\Abstracts;

if ( ! class_exists( '\WPGraphQL\Login\Vendor\AxeWP\GraphQL\Abstracts\EnumType' ) ) {

	/**
	 * Class - EnumType
	 */
	abstract class EnumType extends Type {
		/**
		 * Gets the Enum values configuration array.
		 *
		 * @return array<string,array{description:string,value:mixed,deprecationReason?:string}>
		 */
		abstract public static function get_values(): array;

		/**
		 * {@inheritDoc}
		 */
		public static function register(): void {
			register_graphql_enum_type( static::get_type_name(), static::get_type_config() );
		}

		/**
		 * {@inheritDoc}
		 */
		protected static function get_type_config(): array {
			$config = parent::get_type_config();

			$config['values'] = static::get_values();

			return $config;
		}
	}
}
