<?php
/**
 * The GraphQL LinkedIdentity object type.
 *
 * @package WPGraphQL\Login\Type\WPObject
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Type\WPObject;

use WPGraphQL\Login\Type\Enum\ProviderEnum;
use WPGraphQL\Login\Vendor\AxeWP\GraphQL\Abstracts\ObjectType;

/**
 * Class - LinkedIdentity
 */
class LinkedIdentity extends ObjectType {
	/**
	 * {@inheritDoc}
	 */
	public static function type_name(): string {
		return 'LinkedIdentity';
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_description(): string {
		return __( 'The linked identity from the login provider.', 'wp-graphql-headless-login' );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_fields(): array {
		return [
			'provider' => [
				'type'        => ProviderEnum::get_type_name(),
				'description' => __( 'The login provider which provided the identity.', 'wp-graphql-headless-login' ),
			],
			'id'       => [
				'type'        => 'ID',
				'description' => __( 'The internal user identifier from the login provider.', 'wp-graphql-headless-login' ),
			],
		];
	}
}
