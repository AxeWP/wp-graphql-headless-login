<?php
/**
 * The Password ProviderResponseInput GraphQL Object.
 *
 * @package WPGraphQL\Login\Type\Input
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Type\Input;

use WPGraphQL\Login\Vendor\AxeWP\GraphQL\Abstracts\InputType;

/**
 * Class - PasswordProviderResponseInput
 */
class PasswordProviderResponseInput extends InputType {
	/**
	 * {@inheritDoc}
	 */
	public static function type_name(): string {
		return 'PasswordProviderResponseInput';
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_description(): string {
		return __( 'The parsed response from the Password Provider.', 'wp-graphql-headless-login' );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_fields(): array {
		return [
			'username' => [
				'type'        => [ 'non_null' => 'String' ],
				'description' => __( 'The WordPress username to authenticate ass', 'wp-graphql-headless-login' ),
			],
			'password' => [
				'type'        => [ 'non_null' => 'String' ],
				'description' => __( 'The password for the WordPress user.', 'wp-graphql-headless-login' ),
			],
		];
	}
}
