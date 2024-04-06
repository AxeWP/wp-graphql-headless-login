<?php
/**
 * Registers fields to RootQuery
 *
 * @package WPGraphQL\Login\Fields
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Fields;

use WPGraphQL\Login\Auth\Client as AuthClient;
use WPGraphQL\Login\Auth\ProviderRegistry;
use WPGraphQL\Login\Model\Client as ClientModel;
use WPGraphQL\Login\Type\Enum\ProviderEnum;
use WPGraphQL\Login\Type\WPObject\Client;
use WPGraphQL\Login\Vendor\AxeWP\GraphQL\Abstracts\FieldsType;

/**
 * Class - RootQuery
 */
class RootQuery extends FieldsType {
	/**
	 * {@inheritDoc}
	 */
	protected static function type_name(): string {
		return 'RootQuery';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public static function get_type_name(): string {
		return static::type_name();
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_fields(): array {
		return [
			'loginClients' => [
				'type'        => [ 'list_of' => Client::get_type_name() ],
				'description' => __( 'The registered Headless Login clients.', 'wp-graphql-headless-login' ),
				'resolve'     => static function (): ?array {
					$providers = ProviderRegistry::get_instance()->get_providers();

					$clients = [];
					foreach ( array_keys( $providers ) as $provider ) {
						$client = new AuthClient( $provider );

						$clients[] = new ClientModel( $client );
					}

					return ! empty( $clients ) ? $clients : null;
				},
			],
			'loginClient'  => [
				'type'        => Client::get_type_name(),
				'description' => __( 'The Headless Login client for the provided client ID.', 'wp-graphql-headless-login' ),
				'args'        => [
					'provider' => [
						'type'        => [ 'non_null' => ProviderEnum::get_type_name() ],
						'description' => __( 'The Provider slug.', 'wp-graphql-headless-login' ),
					],
				],
				'resolve'     => static function ( $source, array $args ): ClientModel {
					// Get the client from the provider config.
					$client = new AuthClient( $args['provider'] );

					return new ClientModel( $client );
				},
			],
		];
	}
}
