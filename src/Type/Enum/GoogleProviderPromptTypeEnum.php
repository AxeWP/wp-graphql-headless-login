<?php
/**
 * The GoogleProviderPromptTypeEnum.
 *
 * @package WPGraphQL\Login\Type\Enum
 * @since 0.0.1
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Type\Enum;

use WPGraphQL\Login\Vendor\AxeWP\GraphQL\Abstracts\EnumType;

/**
 * Class - GoogleProviderPromptTypeEnum
 */
class GoogleProviderPromptTypeEnum extends EnumType {
	/**
	 * {@inheritDoc}
	 */
	public static function type_name(): string {
		return 'GoogleProviderPromptTypeEnum';
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_description(): string {
		return __( 'The Google OAuth2 Provider prompt type.', 'wp-graphql-headless-login' );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_values(): array {
		return [
			'NONE'           => [
				'value'       => 'none',
				'description' => __( 'The authorization server does not display any authentication or user consent screens; it will return an error if the user is not already authenticated and has not pre-configured consent for the requested scopes. You can use none to check for existing authentication and/or consent.', 'wp-graphql-headless-login' ),
			],
			'CONSENT'        => [
				'value'       => 'consent',
				'description' => __( 'TThe authorization server prompts the user for consent before returning information to the client.', 'wp-graphql-headless-login' ),
			],
			'SELECT_ACCOUNT' => [
				'value'       => 'select_account',
				'description' => __( 'The authorization server prompts the user to select a user account. This allows a user who has multiple accounts at the authorization server to select amongst the multiple accounts that they may have current sessions for.', 'wp-graphql-headless-login' ),
			],
		];
	}
}
