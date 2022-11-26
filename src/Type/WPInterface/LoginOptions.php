<?php
/**
 * The LoginOptions GraphQL Object.
 *
 * @package WPGraphQL\Login\Type\WPInterface
 */

namespace WPGraphQL\Login\Type\WPInterface;

use WPGraphQL;
use WPGraphQL\Login\Auth\ProviderConfig\ProviderConfig;
use WPGraphQL\Login\Vendor\AxeWP\GraphQL\Abstracts\InterfaceType;
use WPGraphQL\Login\Vendor\AxeWP\GraphQL\Traits\TypeResolverTrait;

/**
 * Class - LoginOptions
 */
class LoginOptions extends InterfaceType {
	use TypeResolverTrait;

	/**
	 * The WPGraphQL TypeRegistry instance.
	 *
	 * @var ?\WPGraphQL\Registry\TypeRegistry
	 */
	protected static $type_registry = null;


	/**
	 * {@inheritDoc}
	 */
	public static function register( $type_registry = null ) : void {
		self::$type_registry = WPGraphQL::get_type_registry();

		register_graphql_interface_type( static::get_type_name(), static::get_type_config() );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function type_name() : string {
		return 'LoginOptions';
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_description() : string {
		return __( 'The login options for the Headless Login provider.', 'wp-graphql-headless-login' );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_fields() : array {
		return ProviderConfig::default_login_options_fields();
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param array $value The value.
	 */
	public static function get_resolved_type_name( $value ): ?string {
		return graphql_format_type_name( ucfirst( $value['__typename'] ) . 'LoginOptions' );
	}
}
