<?php
/**
 * Registers the individual Login Options for each provider.
 *
 * @package WPGraphQL\Login\Type\WPObject
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Type\WPObject;

use WPGraphQL\Login\Auth\ProviderRegistry;
use WPGraphQL\Login\Type\WPInterface\LoginOptions as LoginOptionsInterface;
use WPGraphQL\Login\Vendor\AxeWP\GraphQL\Abstracts\Type;
use WPGraphQL\Login\Vendor\AxeWP\GraphQL\Helper\Compat;

/**
 * Class - LoginOptions
 *
 * phpcs:disable SlevomatCodingStandard.Namespaces.FullyQualifiedClassNameInAnnotation -- PHPStan formatting.
 *
 * @phpstan-import-type ObjectTypeConfig from \WPGraphQL\Login\Vendor\AxeWP\GraphQL\Abstracts\ObjectType
 * @extends \WPGraphQL\Login\Vendor\AxeWP\GraphQL\Abstracts\Type<ObjectTypeConfig>
 *
 * phpcs:enable SlevomatCodingStandard.Namespaces.FullyQualifiedClassNameInAnnotation
 */
class LoginOptions extends Type {
	/**
	 * {@inheritDoc}
	 */
	public static function register(): void {
		$providers = ProviderRegistry::get_instance()->get_registered_providers();

		foreach ( $providers as $slug => $provider ) {
			$name   = static::type_name( $slug );
			$fields = $provider::get_login_options_fields();
			$config = [
				'description'     => static fn () => sprintf(
					self::get_description(),
					$slug
				),
				'fields'          => $fields,
				'interfaces'      => [ LoginOptionsInterface::get_type_name() ],
				'eagerlyLoadType' => true,
			];

			// @todo Remove this when WPGraphQL 2.3.0 is minimum.
			$config = Compat::resolve_graphql_config( $config );

			register_graphql_object_type( $name, $config );
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public static function type_name( ?string $provider = null ): string {
		return graphql_format_type_name( ucfirst( (string) $provider ) . 'LoginOptions' );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_description(): string {
		// translators: %s is the provider name.
		return __( 'The Headless Login options for the %s provider.', 'wp-graphql-headless-login' );
	}
}
