<?php
/**
 * Registers the Access Control Settings
 *
 * @package WPGraphQL\Login\Settings
 * @since 0.0.6
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Settings;

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
	public function get_label(): string {
		return __( 'Access Control', 'wp-graphql-headless-login' );
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
			'shouldBlockUnauthorizedDomains' => [
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
			// Has Site Address In Origin.
			'hasSiteAddressInOrigin'         => [
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
			'additionalAuthorizedDomains'    => [
				'description'       => __( 'An array additional authorized domains to include in the Access-Control-Allow-Origin header.', 'wp-graphql-headless-login' ),
				'label'             => __( 'Additional authorized domains', 'wp-graphql-headless-login' ),
				'type'              => 'array',
				'default'           => [],
				'help'              => __( 'Domains added here will also be included in the `Access-Control-Allow-Origin` header. Make sure to include the protocol (http:// or https://). Wildcards (`*`) are not supported.', 'wp-graphql-headless-login' ),
				'isAdvanced'        => true,
				'order'             => 4,
				'required'          => false,
				'sanitize_callback' => static function ( $value ) {
					if ( is_string( $value ) ) {
						$value = explode( ',', $value );
					}

					if ( ! is_array( $value ) ) {
						return [];
					}

					// Wildcards aren't supported, since they can't be matched against the request origin.
					$domains = array_map(
						static fn ( $domain ) => '*' === trim( (string) $domain ) ? '' : esc_url_raw( (string) $domain ),
						$value
					);

					return array_values( array_filter( $domains ) );
				},
				'validate_callback' => static function ( $value ) {
					$domains = is_string( $value ) ? explode( ',', $value ) : (array) $value;

					foreach ( $domains as $domain ) {
						if ( '*' === trim( (string) $domain ) ) {
							return new \WP_Error(
								'rest_invalid_authorized_domain',
								__( 'The `*` wildcard is not supported. To allow requests from any domain, disable "Block unauthorized domains" instead.', 'wp-graphql-headless-login' ),
								[ 'status' => 400 ]
							);
						}
					}

					return true;
				},
			],
			// Custom Headers.
			'customHeaders'                  => [
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
		];
	}
}
