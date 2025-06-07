<?php
/**
 * The Login Client GraphQL object.
 *
 * @package WPGraphQL\Login\Type\WPObject
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Type\WPObject;

use WPGraphQL\Login\Type\Enum\ProviderEnum;
use WPGraphQL\Login\Type\WPInterface\ClientOptions;
use WPGraphQL\Login\Type\WPInterface\LoginOptions;
use WPGraphQL\Login\Vendor\AxeWP\GraphQL\Abstracts\ObjectType;

/**
 * Class - Client
 */
class Client extends ObjectType {
	/**
	 * {@inheritDoc}
	 */
	public static function type_name(): string {
		return 'LoginClient';
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_description(): string {
		return __( 'The Headless Login client.', 'wp-graphql-headless-login' );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_fields(): array {
		return [
			'authorizationUrl' => [
				'type'        => 'String',
				'description' => static fn () => __( 'The authorization URL.', 'wp-graphql-headless-login' ),
			],
			'clientId'         => [
				'type'        => 'ID',
				'description' => static fn () => __( 'The client ID.', 'wp-graphql-headless-login' ),
			],
			'clientOptions'    => [
				'type'        => ClientOptions::get_type_name(),
				'description' => static fn () => __( 'The client options.', 'wp-graphql-headless-login' ),
			],
			'isEnabled'        => [
				'type'        => 'Boolean',
				'description' => static fn () => __( 'Whether the client is enabled.', 'wp-graphql-headless-login' ),
			],
			'loginOptions'     => [
				'type'        => LoginOptions::get_type_name(),
				'description' => static fn () => __( 'The login options.', 'wp-graphql-headless-login' ),
			],
			'name'             => [
				'type'        => 'String',
				'description' => static fn () => __( 'The client name.', 'wp-graphql-headless-login' ),
			],
			'order'            => [
				'type'        => 'Int',
				'description' => static fn () => __( 'A field used for ordering clients.', 'wp-graphql-headless-login' ),
			],
			'provider'         => [
				'type'        => ProviderEnum::get_type_name(),
				'description' => static fn () => __( 'The provider type.', 'wp-graphql-headless-login' ),
			],
		];
	}
}
