<?php
/**
 * Registers the Cookie Settings
 *
 * @package WPGraphQL\Login\Admin\Settings
 * @since @todo
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Admin\Settings;

/**
 * Class CookieSettings
 */
class CookieSettings extends AbstractSettings {
	/**
	 * {@inheritDoc}
	 */
	public static function get_slug(): string {
		return self::SETTINGS_PREFIX . 'cookies';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_title(): string {
		return __( 'Cookie Settings', 'wp-graphql-headless-login' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_description(): string {
		return __( 'Manage Cookie generation, headers, and settings for the plugin.', 'wp-graphql-headless-login' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_config(): array {
		return [
			// Has Access Control Allow Credentials.
			'hasAccessControlAllowCredentials' => [
				'description'       => __( 'Whether the `Access-Control-Allow-Credentials` header should be added to the request.', 'wp-graphql-headless-login' ),
				'label'             => __( 'Add Access-Control-Allow-Credentials', 'wp-graphql-headless-login' ),
				'type'              => 'boolean',
				'isAdvanced'        => false,
				'default'           => false,
				'help'              => __( 'If enabled, the `Access-Control-Allow-Credentials` header will be included in the request. Requires `Access Control > Block Unauthorized Domains` to be enabled.', 'wp-graphql-headless-login' ),
				'order'             => 1,
				'required'          => false,
				'sanitize_callback' => 'rest_sanitize_boolean',
				// @todo add `disabled` attribute.
			],
			// Has Logout Mutation.
			'hasLogoutMutation'                => [
				'description'       => __( 'Whether the `logout` mutation should be exposed to the GraphQL schema.', 'wp-graphql-headless-login' ),
				'label'             => __( 'Enable Logout Mutation', 'wp-graphql-headless-login' ),
				'type'              => 'boolean',
				'default'           => false,
				'help'              => __( 'If enabled, the `logout` mutation will be exposed to the GraphQL schema, which will clear the user\'s session.', 'wp-graphql-headless-login' ),
				'isAdvanced'        => false,
				'order'             => 2,
				'required'          => false,
				'sanitize_callback' => 'rest_sanitize_boolean',
				'conditionalLogic'  => [
					'slug'     => 'hasAccessControlAllowCredentials',
					'operator' => '==',
					'value'    => true,
				],
			],
			// SameSite Option.
			'sameSiteOption'                   => [
				'description'       => __( 'Specify the SameSite attribute for authentication.', 'wp-graphql-headless-login' ),
				'label'             => __( 'Samesite Cookie Mode', 'wp-graphql-headless-login' ),
				'type'              => 'string',
				'controlType'       => 'select',
				'default'           => 'Lax',
				'help'              => __( 'Choose "None" if cross-site access is required, "Lax" for moderate protection, or "Strict" for maximum protection.', 'wp-graphql-headless-login' ),
				'isAdvanced'        => true,
				'order'             => 3,
				'required'          => false,
				'enum'              => [
					'Lax',
					'None',
					'Strict',
				],
				'sanitize_callback' => 'sanitize_text_field',
				'conditionalLogic'  => [
					'slug'     => 'hasAccessControlAllowCredentials',
					'operator' => '==',
					'value'    => true,
				],
			],
			// Login Cookie Domain.
			'cookieDomain'                     => [
				'description'       => __( 'Override the cookie domain.', 'wp-graphql-headless-login' ),
				'label'             => __( 'Cookie Domain', 'wp-graphql-headless-login' ),
				'type'              => 'string',
				'default'           => '',
				'help'              => __( 'Leave blank by default. To share across all subdomains, use your root domain prefixed with a period (e.g., .mysite.com).', 'wp-graphql-headless-login' ),
				'isAdvanced'        => true,
				'order'             => 4,
				'required'          => false,
				'sanitize_callback' => 'sanitize_text_field',
				'conditionalLogic'  => [
					'slug'     => 'hasAccessControlAllowCredentials',
					'operator' => '==',
					'value'    => true,
				],
			],
		];
	}
}
