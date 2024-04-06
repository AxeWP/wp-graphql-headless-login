<?php
/**
 * Abstract class to make it easy to register Input types to WPGraphQL.
 *
 * @package AxeWP\GraphQL\Abstracts
 *
 * @license GPL-3.0-or-later
 * Modified by AxePress Development using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare( strict_types=1 );

namespace WPGraphQL\Login\Vendor\AxeWP\GraphQL\Abstracts;

use WPGraphQL\Login\Vendor\AxeWP\GraphQL\Interfaces\TypeWithInputFields;

if ( ! class_exists( '\WPGraphQL\Login\Vendor\AxeWP\GraphQL\Abstracts\InputType' ) ) {

	/**
	 * Class - InputType
	 */
	abstract class InputType extends Type implements TypeWithInputFields {
		/**
		 * {@inheritDoc}
		 */
		public static function register(): void {
			register_graphql_input_type( static::get_type_name(), static::get_type_config() );
		}

		/**
		 * {@inheritDoc}
		 */
		protected static function get_type_config(): array {
			$config = parent::get_type_config();

			$config['fields'] = static::get_fields();

			return $config;
		}
	}
}
