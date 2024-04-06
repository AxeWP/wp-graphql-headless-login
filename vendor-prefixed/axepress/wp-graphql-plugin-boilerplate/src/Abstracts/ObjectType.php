<?php
/**
 * Abstract class to make it easy to register Object types to WPGraphQL.
 *
 * @package AxeWP\GraphQL\Abstracts
 *
 * @license GPL-3.0-or-later
 * Modified by AxePress Development using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare( strict_types=1 );

namespace WPGraphQL\Login\Vendor\AxeWP\GraphQL\Abstracts;

use WPGraphQL\Login\Vendor\AxeWP\GraphQL\Interfaces\TypeWithFields;

if ( ! class_exists( '\WPGraphQL\Login\Vendor\AxeWP\GraphQL\Abstracts\ObjectType' ) ) {

	/**
	 * Class - ObjectType
	 */
	abstract class ObjectType extends Type implements TypeWithFields {
		/**
		 * {@inheritDoc}
		 */
		public static function register(): void {
			register_graphql_object_type( static::get_type_name(), static::get_type_config() );
		}

		/**
		 * {@inheritDoc}
		 */
		protected static function get_type_config(): array {
			$config = parent::get_type_config();

			$config['fields'] = static::get_fields();

			if ( method_exists( static::class, 'get_connections' ) ) {
				$config['connections'] = static::get_connections();
			}

			if ( method_exists( static::class, 'get_interfaces' ) ) {
				$config['interfaces'] = static::get_interfaces();
			}

			return $config;
		}
	}
}
