<?php
/**
 * Registers the individual Client Options GraphQL objects for each Login provider.
 *
 * @package WPGraphQL\Login\Type\WPObject
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Type\WPObject;

use WPGraphQL\Login\Auth\ProviderRegistry;
use WPGraphQL\Login\Type\WPInterface\ClientOptions as ClientOptionsInterface;
use WPGraphQL\Login\Vendor\AxeWP\GraphQL\Abstracts\Type;

/**
 * Class - ClientOptions
 */
class ClientOptions extends Type {
	/**
	 * {@inheritDoc}
	 */
	public static function register(): void {
		$providers = ProviderRegistry::get_instance()->get_registered_providers();

		foreach ( $providers as $slug => $provider ) {
			$name   = static::type_name( $slug );
			$fields = $provider::get_client_options_fields();
			$config = [
				'description'     => sprintf(
					self::get_description(),
					$slug
				),
				'fields'          => $fields,
				'interfaces'      => [ ClientOptionsInterface::get_type_name() ],
				'eagerlyLoadType' => true,
			];

			register_graphql_object_type( $name, $config );
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public static function type_name( ?string $provider = null ): string {
		return graphql_format_type_name( ucfirst( (string) $provider ) . 'ClientOptions' );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_description(): string {
		// translators: %s is the provider name.
		return __( 'The Login client options for the %s provider.', 'wp-graphql-headless-login' );
	}
}
