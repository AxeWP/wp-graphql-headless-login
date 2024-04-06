<?php
/**
 * The OAuth ProviderResponseInput GraphQL Object.
 *
 * @package WPGraphQL\Login\Type\Input
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Type\Input;

use WPGraphQL\Login\Vendor\AxeWP\GraphQL\Abstracts\InputType;

/**
 * Class - OAuthProviderResponseInput
 */
class OAuthProviderResponseInput extends InputType {
	/**
	 * {@inheritDoc}
	 */
	public static function type_name(): string {
		return 'OAuthProviderResponseInput';
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_description(): string {
		return __( 'The parsed response from the OAuth Provider.', 'wp-graphql-headless-login' );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_fields(): array {
		return [
			'code'  => [
				'type'        => [ 'non_null' => 'String' ],
				'description' => __( 'The authorization code returned from the OAuth provider.', 'wp-graphql-headless-login' ),
			],
			'state' => [
				'type'        => 'String',
				'description' => __( 'The state returned from the OAuth provider.', 'wp-graphql-headless-login' ),
			],
		];
	}
}
