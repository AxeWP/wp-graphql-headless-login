<?php
/**
 * Abstract class to make it easy to register Mutation types to WPGraphQL.
 *
 * @package AxeWP\GraphQL\Abstracts
 *
 * @license GPL-3.0-or-later
 * Modified by AxePress Development using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare( strict_types=1 );

namespace WPGraphQL\Login\Vendor\AxeWP\GraphQL\Abstracts;

if ( ! class_exists( '\WPGraphQL\Login\Vendor\AxeWP\GraphQL\Abstracts\MutationType' ) ) {

	/**
	 * Class - MutationType
	 */
	abstract class MutationType extends Type {
		/**
		 * Gets the input fields for the mutation.
		 *
		 * @return array<string,array{type:string|array<string,string|array<string,string>>,description:string,defaultValue?:string}>
		 */
		abstract public static function get_input_fields(): array;

		/**
		 * Gets the fields for the type.
		 *
		 * @return array<string,array{type:string|array<string,string|array<string,string>>,description:string,args?:array<string,array{type:string|array<string,string|array<string,string>>,description:string,defaultValue?:mixed}>,resolve?:callable,deprecationReason?:string}>
		 */
		abstract public static function get_output_fields(): array;

		/**
		 * Defines the mutation data modification closure.
		 */
		abstract public static function mutate_and_get_payload(): callable;

		/**
		 * Register mutations to the GraphQL Schema.
		 */
		public static function register(): void {
			register_graphql_mutation( static::get_type_name(), static::get_type_config() );
		}

		/**
		 * {@inheritDoc}
		 */
		public static function get_description(): string {
			return '';
		}

		/**
		 * {@inheritDoc}
		 */
		protected static function get_type_config(): array {
			$config = parent::get_type_config();

			$config['inputFields']         = static::get_input_fields();
			$config['outputFields']        = static::get_output_fields();
			$config['mutateAndGetPayload'] = static::mutate_and_get_payload();

			return $config;
		}
	}
}
