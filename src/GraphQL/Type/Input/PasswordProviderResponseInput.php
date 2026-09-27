<?php
/**
 * The Password ProviderResponseInput GraphQL Object.
 *
 * @package WPGraphQL\Login\GraphQL\Type\Input
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\GraphQL\Type\Input;

use WPGraphQL\Login\Vendor\AxeWP\Common\GraphQL\Abstracts\InputType;

/**
 * Class - PasswordProviderResponseInput
 */
class PasswordProviderResponseInput extends InputType {
	/**
	 * {@inheritDoc}
	 */
	protected static function type_name(): string {
		return 'PasswordProviderResponseInput';
	}

	/**
	 * {@inheritDoc}
	 */
	protected static function get_description(): string {
		return __( 'The parsed response from the Password Provider.', 'wp-graphql-headless-login' );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_fields(): array {
		return [
			'username' => [
				'type'        => [ 'non_null' => 'String' ],
				'description' => static fn () => __( 'The WordPress username to authenticate ass', 'wp-graphql-headless-login' ),
			],
			'password' => [
				'type'        => [ 'non_null' => 'String' ],
				'description' => static fn () => __( 'The password for the WordPress user.', 'wp-graphql-headless-login' ),
			],
		];
	}
}
