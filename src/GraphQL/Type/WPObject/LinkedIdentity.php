<?php
/**
 * The GraphQL LinkedIdentity object type.
 *
 * @package WPGraphQL\Login\GraphQL\Type\WPObject
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\GraphQL\Type\WPObject;

use WPGraphQL\Login\GraphQL\Type\Enum\ProviderEnum;
use WPGraphQL\Login\Vendor\AxeWP\Common\GraphQL\Abstracts\ObjectType;

/**
 * Class - LinkedIdentity
 */
class LinkedIdentity extends ObjectType {
	/**
	 * {@inheritDoc}
	 */
	protected static function type_name(): string {
		return 'LinkedIdentity';
	}

	/**
	 * {@inheritDoc}
	 */
	protected static function get_description(): string {
		return __( 'The linked identity from the login provider.', 'wp-graphql-headless-login' );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_fields(): array {
		return [
			'provider' => [
				'type'        => ProviderEnum::get_type_name(),
				'description' => static fn () => __( 'The login provider which provided the identity.', 'wp-graphql-headless-login' ),
			],
			'id'       => [
				'type'        => 'ID',
				'description' => static fn () => __( 'The internal user identifier from the login provider.', 'wp-graphql-headless-login' ),
			],
		];
	}
}
