<?php
/**
 * Defines the static methods used by ProviderConfig.
 *
 * @package WPGraphQL\Login\Auth\ProviderConfig
 * @since 0.0.1
 */

namespace WPGraphQL\Login\Auth\ProviderConfig;

use WPGraphQL\Login\Utils\Utils;

trait ProviderConfigStaticTrait {
	/**
	 * Returns whether the provider is enabled in the settings.
	 */
	public static function is_enabled() : bool {
		$config = Utils::get_provider_settings( static::get_slug() );
		return ! empty( $config['isEnabled'] );
	}

	/**
	 * Gets the WPGraphQL fields config for the provider settings.
	 */
	public static function get_client_options_fields() : array {
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
	 */
	public static function get_client_options_schema() : array {
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
	 */
	public static function get_login_options_fields() : array {
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
	 */
	public static function get_login_options_schema() : array {
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
	 */
	public static function default_client_options_fields() : array {
		$fields = [
			'clientId'     => [
				'type'        => 'String',
				'description' => __( 'The client ID.', 'wp-graphql-headless-login' ),
			],
			'clientSecret' => [
				'type'        => 'String',
				'description' => __( 'The client Secret.', 'wp-graphql-headless-login' ),
			],
			'redirectUri'  => [
				'type'        => 'String',
				'description' => __( 'The client redirect URI.', 'wp-graphql-headless-login' ),
			],
		];

		return $fields;
	}

	/**
	 * Gets the default WPGraphQL fields config for the provider client options.
	 */
	public static function default_login_options_fields() : array {
		$fields = [
			'linkExistingUsers'      => [
				'type'        => 'Boolean',
				'description' => __( 'Whether to link existing users.', 'wp-graphql-headless-login' ),
			],
			'createUserIfNoneExists' => [
				'type'        => 'Boolean',
				'description' => __( 'Whether to create users if none exist.', 'wp-graphql-headless-login' ),
			],
		];

		return $fields;
	}

	/**
	 * Returns the schema properties for the client options.
	 *
	 * @see ProviderConfig::client_options_schema().
	 */
	protected static function default_client_options_schema() : array {
		$settings = [
			'clientId'     => [
				'type'        => 'string',
				'description' => __( 'Client ID.', 'wp-graphql-headless-login' ),
				'order'       => 0,
			],
			'clientSecret' => [
				'type'        => 'string',
				'description' => __( 'Client Secret.', 'wp-graphql-headless-login' ),
				'order'       => 1,
			],
			'redirectUri'  => [
				'type'        => 'string',
				'description' => __( 'Redirect URI.', 'wp-graphql-headless-login' ),
				'help'        => __( 'The frontend URL to redirect the user to after authorization.', 'wp-graphql-headless-login' ),
				'order'       => 2,
			],
		];

		return $settings;
	}


	/**
	 * Returns the default schema properties for the Login options.
	 *
	 * @see ProviderConfig::login_options_schema().
	 */
	protected static function default_login_options_schema() : array {
		$settings = [
			'linkExistingUsers'      => [
				'type'        => 'boolean',
				'description' => __( 'Login existing users.', 'wp-graphql-headless-login' ),
				'required'    => true,
				'help'        => __( 'If a WordPress account already exists with the same identity as a newly-authenticated user, login as that user instead of generating an error.', 'wp-graphql-headless-login' ),
				'order'       => 0,
			],
			'createUserIfNoneExists' => [
				'type'        => 'boolean',
				'description' => __( 'Create new users.', 'wp-graphql-headless-login' ),
				'help'        => __( 'If the user identity is not linked to an existing WordPress user, it is created. If this setting is not enabled, and if the user authenticates with an account which is not linked to an existing WordPress user, then the authentication will fail.', 'wp-graphql-headless-login' ),
				'required'    => true,
				'order'       => 1,
			],
		];

		return $settings;
	}



}
