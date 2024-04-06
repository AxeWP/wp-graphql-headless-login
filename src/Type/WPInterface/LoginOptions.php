<?php
/**
 * The LoginOptions GraphQL Object.
 *
 * @package WPGraphQL\Login\Type\WPInterface
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Type\WPInterface;

use WPGraphQL\Login\Auth\ProviderConfig\ProviderConfig;
use WPGraphQL\Login\Vendor\AxeWP\GraphQL\Abstracts\InterfaceType;
use WPGraphQL\Login\Vendor\AxeWP\GraphQL\Traits\TypeResolverTrait;

/**
 * Class - LoginOptions
 */
class LoginOptions extends InterfaceType {
	use TypeResolverTrait;

	/**
	 * {@inheritDoc}
	 */
	public static function type_name(): string {
		return 'LoginOptions';
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_description(): string {
		return __( 'The login options for the Headless Login provider.', 'wp-graphql-headless-login' );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_fields(): array {
		return ProviderConfig::default_login_options_fields();
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param array<string,mixed> $value The value.
	 */
	public static function get_resolved_type_name( $value ): ?string {
		return graphql_format_type_name( ucfirst( $value['__typename'] ) . 'LoginOptions' );
	}
}
