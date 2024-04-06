<?php
/**
 * Defines the static methods used by ProviderConfig.
 *
 * @package WPGraphQL\Login\Auth\ProviderConfig
 * @since 0.0.1
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Auth\ProviderConfig;

use WPGraphQL\Login\Utils\Utils;

trait ProviderConfigStaticTrait {
	/**
	 * Returns whether the provider is enabled in the settings.
	 */
	public static function is_enabled(): bool {
		$config = Utils::get_provider_settings( static::get_slug() );
		return ! empty( $config['isEnabled'] );
	}

	/**
	 * Gets the WPGraphQL fields config for the provider settings.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function get_client_options_fields(): array {
		$fields = array_merge(
			static::default_client_options_fields(),
			static::client_options_fields(),
		);

		/**
		 * Filters the GraphQL fields for the provider's Client Options.
		 *
		 * @param array $fields An array of WPGraphQL field $configs.
		 * @param string $slug The provider slug.
		 */
		$fields = apply_filters( 'graphql_login_client_options_fields', $fields, static::get_slug() );

		return apply_filters( 'graphql_login_' . static::get_slug() . '_client_options_fields', $fields );
	}

	/**
	 * Gets the WP REST schema config for the client options.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function get_client_options_schema(): array {
		$settings = array_merge(
			static::default_client_options_schema(),
			static::client_options_schema(),
		);

		/**
		 * Filters the WP REST schema for the provider's Client Options settings.
		 *
		 * Useful for modifying Client Options displayed in the admin.
		 *
		 * @param array  $settings An array of WP REST schema $configs.
		 * @param string $slug     The provider slug.
		 */
		$settings = apply_filters( 'graphql_login_client_options_schema', $settings, static::get_slug() );

		return apply_filters( 'graphql_login_' . static::get_slug() . '_client_options_schema', $settings );
	}

	/**
	 * Gets the WPGraphQL fields config for the provider settings.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function get_login_options_fields(): array {
		$fields = array_merge(
			static::default_login_options_fields(),
			static::login_options_fields(),
		);

		/**
		 * Filters the GraphQL fields for the provider's Client Options.
		 *
		 * @param array $fields An array of WPGraphQL field $configs.
		 * @param string $slug The provider slug.
		 */
		$fields = apply_filters( 'graphql_login_login_options_fields', $fields, static::get_slug() );

		return apply_filters( 'graphql_login_' . static::get_slug() . '_login_options_fields', $fields );
	}

	/**
	 * Gets the WP REST schema config for the Login options.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function get_login_options_schema(): array {
		$settings = array_merge(
			static::default_login_options_schema(),
			static::login_options_schema(),
		);

		/**
		 * Filters the WP REST schema for the provider's Login Options settings.
		 *
		 * Useful for modifying Client Options displayed in the admin.
		 *
		 * @param array  $settings An array of WP REST schema $configs.
		 * @param string $slug     The provider slug.
		 */
		$settings = apply_filters( 'graphql_login_login_options_schema', $settings, static::get_slug() );

		return apply_filters( 'graphql_login_' . static::get_slug() . '_login_options_schema', $settings );
	}

	/**
	 * Gets the default WPGraphQL fields config for the provider client options.
	 *
	 * @return array<string,array{
	 *   type: string|array<string,string | array<string,string>>,
	 *   description: string,
	 *   args?: array<string,array{
	 *     type: string|array<string,string | array<string,string>>,
	 *     description: string,
	 *     defaultValue?: mixed
	 *   }>,
	 *   resolve?: callable,
	 *   deprecationReason?: string,
	 * }>
	 */
	public static function default_client_options_fields(): array {
		return [
			'todo' => [
				'type'        => 'Boolean',
				'description' => __( 'This field exists solely to generate the  ClientOptions interface, in lieu of the shared custom fields that will be added in a future release', 'wp-graphql-headless-login' ),
			],
		];
	}

	/**
	 * Gets the default WPGraphQL fields config for the provider client options.
	 *
	 * @return array<string,array{
	 *   type: string|array<string,string | array<string,string>>,
	 *   description: string,
	 *   args?: array<string,array{
	 *     type: string|array<string,string | array<string,string>>,
	 *     description: string,
	 *     defaultValue?: mixed
	 *   }>,
	 *   resolve?: callable,
	 *   deprecationReason?: string,
	 * }>
	 */
	public static function default_login_options_fields(): array {
		return [
			'useAuthenticationCookie' => [
				'type'        => 'Boolean',
				'description' => __( 'Whether to set a WordPress authentication cookie on successful login.', 'wp-graphql-headless-login' ),
			],
		];
	}

	/**
	 * Returns the schema properties for the client options.
	 *
	 * @see ProviderConfig::client_options_schema().
	 *
	 * @return array<string,array<string,mixed>>
	 */
	protected static function default_client_options_schema(): array {
		return [];
	}

	/**
	 * Returns the default schema properties for the Login options.
	 *
	 * @see ProviderConfig::login_options_schema().
	 *
	 * @return array<string,array<string,mixed>>
	 */
	protected static function default_login_options_schema(): array {
		return [
			'useAuthenticationCookie' => [
				'type'        => 'boolean',
				'description' => __( 'Set authentication cookie', 'wp-graphql-headless-login' ),
				'help'        => __( 'If enabled, a WordPress authentication cookie will be set after a successful login. This is useful for granting access to the WordPress dashboard or other protected areas of the WordPress backend without having to re-authenticate.', 'wp-graphql-headless-login' ),
				'order'       => 2,
			],
		];
	}
}
