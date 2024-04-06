<?php
/**
 * The Provider Enum.
 *
 * @package WPGraphQL\Login\Type\Enum
 * @since 0.0.1
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Type\Enum;

use WPGraphQL\Login\Auth\ProviderRegistry;
use WPGraphQL\Login\Vendor\AxeWP\GraphQL\Abstracts\EnumType;
use WPGraphQL\Type\WPEnumType;

/**
 * Class - ProviderEnum
 */
class ProviderEnum extends EnumType {
	/**
	 * {@inheritDoc}
	 */
	public static function type_name(): string {
		return 'LoginProviderEnum';
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_description(): string {
		return __( 'The Headless Login Provider.', 'wp-graphql-headless-login' );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_values(): array {
		$providers = ProviderRegistry::get_instance()->get_registered_providers();

		$values = [];
		foreach ( $providers as $provider ) {
			$name = WPEnumType::get_safe_name( $provider::get_slug() );

			$values[ $name ] = [
				'value'       => $provider::get_slug(),
				'description' => sprintf(
					// translators: Headless Login provider name.
					__( 'The %s provider.', 'wp-graphql-headless-login' ),
					$provider::get_name()
				),
			];
		}

		if ( empty( $values ) ) {
			$values['NONE'] = [
				'value'       => 'none',
				'description' => __( 'No Login Providers are currently enabled.', 'wp-graphql-headless-login' ),
			];
		}

		return $values;
	}
}
