<?php
/**
 * Interface for for classes that register a GraphQL type with input fields to the GraphQL schema.
 *
 * @package AxeWP\GraphQL\Interfaces
 *
 * @license GPL-3.0-or-later
 * Modified by AxePress Development using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare( strict_types=1 );

namespace WPGraphQL\Login\Vendor\AxeWP\GraphQL\Interfaces;

if ( ! interface_exists( '\WPGraphQL\Login\Vendor\AxeWP\GraphQL\Interfaces\TypeWithInputFields' ) ) {

	/**
	 * Interface - TypeWithInputFields.
	 */
	interface TypeWithInputFields extends GraphQLType {
		/**
		 * Gets the input fields for the type.
		 *
		 * @return array<string,array{type:string|array<string,string|array<string,string>>,description:string,defaultValue?:string}>
		 */
		public static function get_fields(): array;
	}
}
