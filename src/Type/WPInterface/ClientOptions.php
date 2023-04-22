<?php
/**
 * The ClientOptions GraphQL Object.
 *
 * @package WPGraphQL\Login\Type\WPInterface
 */

namespace WPGraphQL\Login\Type\WPInterface;

use WPGraphQL;
use WPGraphQL\Login\Auth\ProviderConfig\ProviderConfig;
use WPGraphQL\Login\Vendor\AxeWP\GraphQL\Abstracts\InterfaceType;
use WPGraphQL\Login\Vendor\AxeWP\GraphQL\Traits\TypeResolverTrait;

/**
 * Class - ClientOptions
 */
class ClientOptions extends InterfaceType {
	use TypeResolverTrait;

	/**
	 * {@inheritDoc}
	 */
	public static function register( $type_registry = null ) : void {
		$registry = $type_registry instanceof \WPGraphQL\Registry\TypeRegistry ? $type_registry : WPGraphQL::get_type_registry();

		parent::register( $registry );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function type_name() : string {
		return 'LoginClientOptions';
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_description() : string {
		return __( 'The Client Options for the Headless Login provider.', 'wp-graphql-headless-login' );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_fields() : array {
		return ProviderConfig::default_client_options_fields();
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param array $value The value.
	 */
	public static function get_resolved_type_name( $value ): ?string {
		return graphql_format_type_name( ucfirst( $value['__typename'] ) . 'ClientOptions' );
	}
}
