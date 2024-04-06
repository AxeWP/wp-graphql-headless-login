<?php
/**
 * Registers the Provider Settings
 *
 * @package WPGraphQL\Login\Admin\Settings
 * @since 0.0.6
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Admin\Settings;

use WPGraphQL\Login\Auth\ProviderRegistry;

/**
 * Class ProviderSettings
 */
class ProviderSettings {
	/**
	 * The settings key used to store the Clients config.
	 *
	 * @var string
	 */
	public static string $settings_prefix = 'wpgraphql_login_provider_';

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
			$providers = ProviderRegistry::get_instance()->get_registered_providers();

			$config = [];

			foreach ( $providers as $slug => $provider ) {
				$config[ self::$settings_prefix . $slug ] = [
					'name'          => [
						'advanced'    => false,
						'default'     => $provider::get_name(),
						'description' => __( 'The provider name.', 'wp-graphql-headless-login' ),
						'help'        => __( 'This is the name that will be displayed to the user.', 'wp-graphql-headless-login' ),
						'label'       => __( 'Client Label', 'wp-graphql-headless-login' ),
						'order'       => 1,
						'required'    => true,
						'type'        => 'string',
					],
					'order'         => [
						'advanced'    => false,
						'default'     => 0,
						'description' => __( 'The order in which the provider should disappear.', 'wp-graphql-headless-login' ),
						'help'        => __( 'This is the order in which the provider will be displayed to the user.', 'wp-graphql-headless-login' ),
						'label'       => __( 'Order', 'wp-graphql-headless-login' ),
						'hidden'      => true,
						'required'    => true,
						'type'        => 'integer',
					],
					'slug'          => [
						'advanced'    => false,
						'default'     => $slug,
						'description' => __( 'The provider slug.', 'wp-graphql-headless-login' ),
						'help'        => __( 'This is the slug that will be used to identify the provider.', 'wp-graphql-headless-login' ),
						'label'       => __( 'Provider Slug', 'wp-graphql-headless-login' ),
						'enum'        => array_keys( $providers ),
						'hidden'      => true,
						'required'    => true,
						'type'        => 'string',
					],
					'isEnabled'     => [
						'type'        => 'boolean',
						'description' => __( 'Whether the provider is enabled or not.', 'wp-graphql-headless-login' ),
						'label'       => __( 'Enable Provider', 'wp-graphql-headless-login' ),
						'required'    => true,
						'hidden'      => false,
						'order'       => 0,
					],
					'clientOptions' => [
						'type'       => 'object',
						'properties' => $provider::get_client_options_schema(),
					],
					'loginOptions'  => [
						'type'       => 'object',
						'properties' => $provider::get_login_options_schema(),
					],
				];
			}

			self::$config = $config;
		}

		return self::$config;
	}

	/**
	 * Returns the args used to register the settings.
	 *
	 * @return array<string,mixed>
	 */
	public static function get_settings_args(): array {
		if ( empty( self::$args ) ) {
			$config = self::get_config();

			$providers = ProviderRegistry::get_instance()->get_registered_providers();

			$args = [];

			$excluded_keys = [
				'advanced',
				'default',
				'help',
				'label',
				'order',
				'required',
			];

			foreach ( $providers as $slug => $provider ) {
				$defaults = [];

				foreach ( $config[ self::$settings_prefix . $slug ] as $setting_key => $setting_args ) {
					$defaults[ $setting_key ] = $setting_args['default'] ?? null;

					// Remove excluded keys from args.
					$config[ self::$settings_prefix . $slug ][ $setting_key ] = array_diff_key( $setting_args, array_flip( $excluded_keys ) );
				}

				$args[ self::$settings_prefix . $slug ] = [
					'single'          => false,
					'type'            => 'object',
					'default'         => $defaults,
					'show_in_graphql' => false,
					'show_in_rest'    => [
						'schema' => [
							'title'      => $provider::get_name(),
							'type'       => 'object',
							'properties' => $config[ self::$settings_prefix . $slug ],
						],
					],
				];
			}

			self::$args = $args;
		}

		return self::$args;
	}
}
