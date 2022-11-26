<?php
/**
 * Abstract class to make it easy to register Interface types to WPGraphQL.
 *
 * @package AxeWP\GraphQL\Abstracts
 *
 * @license GPL-3.0-or-later
 * Modified by AxePress Development using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace WPGraphQL\Login\Vendor\AxeWP\GraphQL\Abstracts;

use WPGraphQL\Login\Vendor\AxeWP\GraphQL\Interfaces\TypeWithFields;

if ( ! class_exists( '\WPGraphQL\Login\Vendor\AxeWP\GraphQL\Abstracts\InterfaceType' ) ) {

	/**
	 * Class - InterfaceType
	 */
	abstract class InterfaceType extends Type implements TypeWithFields {
		/**
		 * The WPGraphQL TypeRegistry instance.
		 *
		 * @var ?\WPGraphQL\Registry\TypeRegistry
		 */
		protected static $type_registry = null;

		/**
		 * {@inheritDoc}
		 *
		 * @param \WPGraphQL\Registry\TypeRegistry $type_registry The WPGraphQL TypeRegistry instance.
		 */
		public static function register( $type_registry = null ) : void {
			self::$type_registry = $type_registry;

			register_graphql_interface_type( static::get_type_name(), static::get_type_config() );
		}

		/**
		 * {@inheritDoc}
		 */
		protected static function get_type_config() : array {
			$config = parent::get_type_config();

			$config['fields'] = static::get_fields();

			if ( method_exists( static::class, 'get_type_resolver' ) ) {
				$config['resolveType'] = static::get_type_resolver();
			}

			if ( method_exists( static::class, 'get_interfaces' ) ) {
				$config['interfaces'] = static::get_interfaces();
			}

			return $config;
		}

		/**
		 * {@inheritDoc}
		 */
		public static function should_load_eagerly(): bool {
			return true;
		}
	}
}
