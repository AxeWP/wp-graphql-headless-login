<?php
/**
 * Registers the Access Control Settings
 *
 * @package WPGraphQL\Login\Admin\Settings
 * @since 0.0.6
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Admin\Settings;

/**
 * Class AccessControlSettings
 */
class AccessControlSettings extends AbstractSettings {
	/**
	 * {@inheritDoc}
	 */
	public static function get_slug(): string {
		return self::SETTINGS_PREFIX . 'access_control';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_title(): string {
		return __( 'Access Control Settings', 'wp-graphql-headless-login' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_description(): string {
		return __( 'Manage Access Control settings for the plugin.', 'wp-graphql-headless-login' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_config(): array {
		return [
			// Should Block Unauthorized Domains.
			'shouldBlockUnauthorizedDomains'   => [
				'description'       => __( 'Whether to block requests from unauthorized domains', 'wp-graphql-headless-login' ),
				'label'             => __( 'Block unauthorized domains', 'wp-graphql-headless-login' ),
				'type'              => 'boolean',
				'default'           => false,
				'help'              => __( 'If enabled, requests from unauthorized domains will throw an error.', 'wp-graphql-headless-login' ),
				'isAdvanced'        => false,
				'order'             => 1,
				'required'          => true,
				'sanitize_callback' => 'rest_sanitize_boolean',
			],
			// Has Access Control Allow Credentials.
			'hasAccessControlAllowCredentials' => [
				'description'       => __( 'Whether the `Access-Control-Allow-Credentials` header should be added to the request.', 'wp-graphql-headless-login' ),
				'label'             => __( 'Add Access-Control-Allow-Credentials', 'wp-graphql-headless-login' ),
				'type'              => 'boolean',
				'isAdvanced'        => false,
				'default'           => false,
				'help'              => __( 'If enabled, the `Access-Control-Allow-Credentials` header will be included in the request. Requires `Block Unauthorized Domains` to be enabled.', 'wp-graphql-headless-login' ),
				'order'             => 2,
				'required'          => false,
				'sanitize_callback' => 'rest_sanitize_boolean',
				'conditionalLogic'  => [
					'slug'     => 'shouldBlockUnauthorizedDomains',
					'operator' => '==',
					'value'    => true,
				],
			],
			// Has Site Address In Origin.
			'hasSiteAddressInOrigin'           => [
				'description'       => __( 'Whether the Site URL should be added to the `Access-Control-Allow-Origin` header', 'wp-graphql-headless-login' ),
				'label'             => __( 'Add Site URL to Access-Control-Allow-Origin', 'wp-graphql-headless-login' ),
				'type'              => 'boolean',
				'default'           => false,
				'help'              => __( 'If enabled, the Site URL will be added to the `Access-Control-Allow-Origin` header. This is the URL defined in Settings > General > Site URL.', 'wp-graphql-headless-login' ),
				'isAdvanced'        => false,
				'order'             => 3,
				'required'          => true,
				'sanitize_callback' => 'rest_sanitize_boolean',
			],
			// Additional Authorized Domains.
			'additionalAuthorizedDomains'      => [
				'description'       => __( 'An array additional authorized domains to include in the Access-Control-Allow-Origin header.', 'wp-graphql-headless-login' ),
				'label'             => __( 'Additional authorized domains', 'wp-graphql-headless-login' ),
				'type'              => 'array',
				'default'           => [],
				'help'              => __( 'Domains added here will also be included in the `Access-Control-Allow-Origin` header. Make sure to include the protocol (http:// or https://).', 'wp-graphql-headless-login' ),
				'isAdvanced'        => true,
				'order'             => 4,
				'required'          => false,
				'sanitize_callback' => static function ( $value ) {
					if ( is_string( $value ) ) {
						$value = explode( ',', $value );
					}

					return is_array( $value ) ? array_map(
						static function ( $domain ) {
							if ( '*' === $domain ) {
								return $domain;
							}

							return esc_url_raw( $domain );
						},
						$value
					) : [];
				},
			],
			// Custom Headers.
			'customHeaders'                    => [
				'description'       => __( 'An array of custom headers to add to the response', 'wp-graphql-headless-login' ),
				'label'             => __( 'Custom Headers', 'wp-graphql-headless-login' ),
				'type'              => 'array',
				'default'           => [],
				'help'              => __( 'These custom headers will be allow-listed to the response. E.g. `X-My-Custom-Header`', 'wp-graphql-headless-login' ),
				'isAdvanced'        => true,
				'order'             => 5,
				'required'          => false,
				'sanitize_callback' => static function ( $value ) {
					return array_map(
						static function ( $header ) {
							return sanitize_text_field( $header );
						},
						$value
					);
				},
			],
			// Login Cookie Same Site Option.
			'loginCookieSameSiteOption'        => [
				'description' => __( 'Login Cookie same-site option (None, Lax, Strict)', 'wp-graphql-headless-login' ),
				'label'       => __( 'Authentication Cookie - Samesite cookie mode', 'wp-graphql-headless-login' ),
				'type'        => 'string',
				'default'     => 'Lax',
				'help'        => __( 'If the "Set authentication cookie" option is enabled, you can choose the SameSite attribute for authentication. Choose "None" if cross-site access is required, "Lax" for moderate protection, or "Strict" for maximum protection.', 'wp-graphql-headless-login' ),
				'isAdvanced'    => true,
				'order'       => 6,
				'required'    => false,
				'enum'        => [
					'Lax',
					'None',
					'Strict',
				],
				'sanitize_callback' => 'sanitize_text_field',
			],
			// Login Cookie Domain.
			'loginCookieDomain'                => [
				'description' => __( 'Login Cookie Domain', 'wp-graphql-headless-login' ),
				'label'       => __( 'Authentication Cookie - Cookie Domain', 'wp-graphql-headless-login' ),
				'type'        => 'string',
				'default'     => '',
				'help'        => __( 'If the "Set authentication cookie" option is enabled, choose the domain for the cookie. Leave blank by default. To share across subdomains, use your root domain prefixed with a period (e.g., .mydomain.com).', 'wp-graphql-headless-login' ),
				'isAdvanced'    => true,
				'order'       => 7,
				'required'    => false,
				'sanitize_callback' => 'sanitize_text_field',
			],
			'hasLogoutMutation'             => [
				'description' => __( 'Enable Logout Mutation', 'wp-graphql-headless-login' ),
				'label'       => __( 'Enable `logout` Mutation', 'wp-graphql-headless-login' ),
				'type'        => 'boolean',
				'default'     => false,
				'help'        => __( 'Enable this option to allow logout mutations in WP GraphQL. This is useful when using cookie authentication, as it allows logout session requests. Note: "Set authentication cookie" and `Access-Control-Allow-Credentials` must be enabled.', 'wp-graphql-headless-login' ),
				'isAdvanced'    => true,
				'order'       => 4,
				'required'    => false,
				'sanitize_callback' => 'rest_sanitize_boolean',
			],
		];
	}
}
