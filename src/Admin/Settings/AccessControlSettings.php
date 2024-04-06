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
class AccessControlSettings {
	/**
	 * The settings key used to store the Clients config.
	 *
	 * @var string
	 */
	public static string $settings_prefix = 'wpgraphql_login_';

	/**
	 * The setting configuration.
	 *
	 * @var array<string,array<string,mixed>>
	 */
	private static array $config = [];

	/**
	 * The args used to register the settings.
	 *
	 * @var array<string,mixed>
	 */
	private static array $args = [];

	/**
	 * Gets the setting configuration.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function get_config(): array {
		if ( empty( self::$config ) ) {
			self::$config = [
				'hasAccessControlAllowCredentials' => [
					'advanced'    => false,
					'default'     => false,
					'description' => __( 'Whether the `Access-Control-Allow-Credentials` header should be added to the request.', 'wp-graphql-headless-login' ),
					'help'        => __( 'If enabled, the `Access-Control-Allow-Credentials` header will be included in the request. Requires `Block Unauthorized Domains` to be enabled.', 'wp-graphql-headless-login' ),
					'label'       => __( 'Add Access-Control-Allow-Credentials', 'wp-graphql-headless-login' ),
					'order'       => 2,
					'required'    => false,
					'type'        => 'boolean',
				],
				'hasSiteAddressInOrigin'           => [
					'advanced'    => false,
					'default'     => false,
					'description' => __( 'Whether the Site URL should be added to the `Access-Control-Allow-Origin` header', 'wp-graphql-headless-login' ),
					'help'        => __( 'If enabled, the Site URL will be added to the `Access-Control-Allow-Origin` header. This is the URL defined in Settings > General > Site URL.', 'wp-graphql-headless-login' ),
					'label'       => __( 'Add Site URL to Access-Control-Allow-Origin', 'wp-graphql-headless-login' ),
					'order'       => 3,
					'required'    => true,
					'type'        => 'boolean',
				],
				'additionalAuthorizedDomains'      => [
					'advanced'    => true,
					'default'     => [],
					'description' => __( 'An array additional authorized domains to include in the Access-Control-Allow-Origin header.', 'wp-graphql-headless-login' ),
					'help'        => __( 'Domains added here will also be included in the `Access-Control-Allow-Origin` header. Make sure to include the protocol (http:// or https://).', 'wp-graphql-headless-login' ),
					'label'       => __( 'Additional authorized domains', 'wp-graphql-headless-login' ),
					'items'       => [
						'type'   => 'string',
						'format' => 'uri',
					],
					'order'       => 4,
					'required'    => false,
					'type'        => 'array',
				],
				'shouldBlockUnauthorizedDomains'   => [
					'advanced'    => false,
					'default'     => false,
					'description' => __( 'Whether to block requests from unauthorized domains', 'wp-graphql-headless-login' ),
					'help'        => __( 'If enabled, requests from unauthorized domains will throw an error.', 'wp-graphql-headless-login' ),
					'label'       => __( 'Block unauthorized domains', 'wp-graphql-headless-login' ),
					'order'       => 1,
					'required'    => true,
					'type'        => 'boolean',
				],
				'customHeaders'                    => [
					'advanced'    => true,
					'default'     => [],
					'description' => __( 'An array of custom headers to add to the response', 'wp-graphql-headless-login' ),
					'help'        => __( 'These custom headers will be allow-listed to the response. E.g. `X-My-Custom-Header`', 'wp-graphql-headless-login' ),
					'items'       => [
						'type' => 'string',
					],
					'label'       => __( 'Custom Headers', 'wp-graphql-headless-login' ),
					'order'       => 5,
					'required'    => false,
					'type'        => 'array',
				],
			];
		}

		return self::$config;
	}

	/**
	 * Returns the args used to register the settings.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function get_settings_args(): array {
		if ( empty( self::$args ) ) {
			$config = self::get_config();

			$excluded_keys = [
				'advanced',
				'default',
				'help',
				'label',
				'order',
				'required',
			];

			$defaults = [];

			foreach ( $config as $settings_key => $args ) {
				$defaults[ $settings_key ] = $args['default'] ?? null;

				// Remove excluded keys from args.
				$config[ $settings_key ] = array_diff_key( $args, array_flip( $excluded_keys ) );
			}

			self::$args = [
				self::$settings_prefix . 'access_control' => [
					'single'            => true,
					'type'              => 'object',
					'default'           => $defaults,
					'show_in_rest'      => [
						'schema' => [
							'title'      => __( 'Access Control Settings', 'wp-graphql-headless-login' ),
							'type'       => 'object',
							'properties' => $config,
						],
					],
					'sanitize_callback' => [ self::class, 'sanitize_callback' ],
				],
			];
		}

		return self::$args;
	}

	/**
	 * Sanitizes the settings.
	 *
	 * @param mixed $value .
	 * @return mixed
	 */
	public static function sanitize_callback( $value ) {
		if ( isset( $value['hasAccessControlAllowCredentials'] ) ) {
			$value['hasAccessControlAllowCredentials'] = (bool) $value['hasAccessControlAllowCredentials'];
		}

		if ( isset( $value['hasSiteAddressInOrigin'] ) ) {
			$value['hasSiteAddressInOrigin'] = (bool) $value['hasSiteAddressInOrigin'];
		}

		if ( isset( $value['shouldBlockUnauthorizedDomains'] ) ) {
			$value['shouldBlockUnauthorizedDomains'] = (bool) $value['shouldBlockUnauthorizedDomains'];
		}

		if ( isset( $value['additionalAuthorizedDomains'] ) ) {
			// Convert string to array.
			if ( is_string( $value['additionalAuthorizedDomains'] ) ) {
				$value['additionalAuthorizedDomains'] = explode( ',', $value['additionalAuthorizedDomains'] );
			}

			$value['additionalAuthorizedDomains'] = is_array( $value['additionalAuthorizedDomains'] ) ? array_map(
				static function ( $domain ) {
					if ( '*' === $domain ) {
						return $domain;
					}

					return esc_url_raw( $domain );
				},
				$value['additionalAuthorizedDomains']
			) : [];
		}

		if ( isset( $value['customHeaders'] ) ) {
			$value['customHeaders'] = array_map(
				static function ( $header ) {
					return sanitize_text_field( $header );
				},
				$value['customHeaders']
			);
		}
		return $value;
	}
}
