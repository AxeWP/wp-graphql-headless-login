<?php
/**
 * Abstract class to make it easy to register Connection types to WPGraphQL.
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

if ( ! class_exists( '\WPGraphQL\Login\Vendor\AxeWP\GraphQL\Abstracts\ConnectionType' ) ) {

	/**
	 * Class - ConnectionType
	 *
	 * @phpstan-type ConnectionConfig array{fromType:string,
	 *   fromFieldName: string,
	 *   resolve: callable,
	 *   oneToOne?: bool,
	 *   toType?: string,
	 *   connectionArgs?: array<string,array{
	 *     type: string|array<string,string | array<string,string>>,
	 *     description: string,
	 *     defaultValue?: mixed
	 *   }>,
	 *   connectionFields?: array<string,array{
	 *     type: string|array<string,string | array<string,string>>,
	 *     description: string,
	 *     args?: array<string,array{
	 *       type: string|array<string,string | array<string,string>>,
	 *       description: string,
	 *       defaultValue?: mixed,
	 *     }>,
	 *     resolve?: callable,
	 *     deprecationReason?: string,
	 *   }>,
	 * }
	 */
	abstract class ConnectionType implements GraphQLType, Registrable {
		use TypeNameTrait;

		/**
		 * {@inheritDoc}
		 */
		public static function init(): void {
			add_action( 'graphql_register_types', [ static::class, 'register' ] );
		}

		/**
		 * Defines all possible connection args for the GraphQL type.
		 *
		 * @return array<string,array{type:string|array<string,string|array<string,string>>,description:string,defaultValue?:mixed}>
		 */
		abstract protected static function connection_args(): array;

		/**
		 * Gets the $config array used to register the connection to the GraphQL type.
		 *
		 * @param ConnectionConfig $config The connection config array.
		 *
		 * @return ConnectionConfig
		 */
		protected static function get_connection_config( $config ): array {
			return array_merge(
				[
					'toType' => static::get_type_name(),
				],
				$config
			);
		}

		/**
		 * Returns a filtered array of connection args.
		 *
		 * @param ?string[] $filter_by an array of specific connections to return.
		 *
		 * @return array<string,array{type:string|array<string,string|array<string,string>>,description:string,defaultValue?:mixed}>
		 */
		final public static function get_connection_args( ?array $filter_by = null ): array {
			$connection_args = static::connection_args();

			if ( empty( $filter_by ) ) {
				return $connection_args;
			}

			$filtered_args = [];
			foreach ( $filter_by as $filter ) {
				$filtered_args[ $filter ] = $connection_args[ $filter ];
			}

			return $filtered_args;
		}
	}
}
