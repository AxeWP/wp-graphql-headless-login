<?php
/**
 * Registers plugin settings to the backend.
 *
 * @package WPGraphQL\Login\Admin
 * @since 0.0.1
 */

namespace WPGraphQL\Login\Admin;

use Error;
use WPGraphQL\Login\Auth\ProviderRegistry;
use WPGraphQL\Login\Auth\TokenManager;

/**
 * Class - Settings
 */
class Settings {

	/**
	 * The name of the plugin option group.
	 *
	 * @var string
	 */
	public static string $option_group = 'wpgraphql_login_settings';

	/**
	 * The section named used in the settings API.
	 *
	 * @var string
	 */
	public static string $settings_prefix = 'wpgraphql_login_settings_';

	/**
	 * The settings key used to store the Clients config.
	 *
	 * @var string
	 */
	public static string $provider_settings_prefix = 'wpgraphql_login_provider_';

	/**
	 * The registered settings.
	 *
	 * @var array
	 */
	private static array $registered_settings = [];

	/**
	 * The registered provider settings.
	 *
	 * @var array
	 */
	private static array $registered_provider_settings = [];

	/**
	 * The admin.css file
	 *
	 * @var string
	 */
	private const ADMIN_CSS = 'build/admin.css';

	/**
	 * {@inheritDoc}
	 */
	public static function init() : void {
		add_action( 'init', [ __CLASS__, 'register_settings' ] );
		add_action( 'graphql_register_settings', [ __CLASS__, 'register_settings_tab' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'register_admin_scripts' ] );
		add_filter( 'rest_pre_get_setting', [ __CLASS__, 'hide_sensitive_data_from_rest' ], 10, 2 );
	}

	/**
	 * Store and get the registered settings.
	 */
	public static function get_settings_config() : array {
		if ( empty( self::$registered_settings ) ) {

			// General Plugin Settings.
			self::$registered_settings = [
				// Admin Display Settings.
				self::$settings_prefix . 'show_advanced_settings' => [
					'default'         => false,
					'single'          => true,
					'type'            => 'boolean',
					'show_in_rest'    => true,
					'show_in_graphql' => false,
				],
				// Delete Data on Deactivate.
				self::$settings_prefix . 'delete_data_on_deactivate' => [
					'default'         => false,
					'single'          => true,
					'type'            => 'boolean',
					'show_in_rest'    => true,
					'show_in_graphql' => false,
				],
				// The JWT Secret.
				self::$settings_prefix . 'jwt_secret_key' => [
					'default'         => false,
					'single'          => true,
					'type'            => 'string',
					'show_in_rest'    => true,
					'show_in_graphql' => false,
				],
			];

			// Provider settings.
			$providers = self::get_provider_settings_config();

			self::$registered_settings = self::$registered_settings + $providers;
		}

		return self::$registered_settings;
	}

	/**
	 * Stores and gets the registered provider settings.
	 *
	 * @return array
	 */
	public static function get_provider_settings_config() : array {
		if ( ! empty( self::$registered_provider_settings ) ) {
			return self::$registered_provider_settings;
		}

		$providers = ProviderRegistry::get_instance()->get_registered_providers();

		foreach ( $providers as $slug => $provider ) {
			self::$registered_provider_settings[ self::$provider_settings_prefix . $slug ] = [
				'single'          => false,
				'type'            => 'object',
				'show_in_rest'    => [
					'schema' => [
						'title'      => $provider::get_name(),
						'type'       => 'object',
						'properties' => [
							'name'          => [
								'type'        => 'string',
								'description' => __( 'The provider name.', 'wp-graphql-headless-login' ),
								'required'    => true,
							],
							'order'         => [
								'type'        => 'integer',
								'description' => __( 'The order in which the provider should disappear.', 'wp-graphql-headless-login' ),
								'required'    => true,
								'hidden'      => true,
							],
							'slug'          => [
								'type'        => 'string',
								'enum'        => array_keys( $providers ),
								'description' => __( 'The provider slug.', 'wp-graphql-headless-login' ),
								'required'    => true,
								'hidden'      => true,
							],
							'isEnabled'     => [
								'type'        => 'boolean',
								'description' => __( 'Whether the provider is enabled or not.', 'wp-graphql-headless-login' ),
								'required'    => true,
								'hidden'      => true,
							],
							'clientOptions' => [
								'type'       => 'object',
								'properties' => $provider::get_client_options_schema(),
							],
							'loginOptions'  => [
								'type'       => 'object',
								'properties' => $provider::get_login_options_schema(),
							],
						],
					],
				],
				'show_in_graphql' => false,
			];
		}

		return self::$registered_provider_settings;
	}

	/**
	 * Register the settings to WordPress.
	 */
	public static function register_settings() : void {
		$registered_settings = self::get_settings_config();

		foreach ( $registered_settings as $setting_name => $config ) {
			register_setting(
				self::$option_group,
				$setting_name,
				$config
			);
		}
	}

	/**
	 * Register the Settings Tab to WPGraphQL.
	 */
	public static function register_settings_tab() : void {
		register_graphql_settings_section(
			self::$option_group,
			[
				'title'    => __( 'Headless Login', 'wp-graphql-headless-login' ),
				'callback' => static function (): void {
					wp_enqueue_script( 'wp-graphql-headless-login/admin-editor' );
					wp_enqueue_script( 'wp-graphql-headless-login/admin-styles' );

					echo wp_kses_post( '<div id="wp-graphql-headless-login-settings"></div>' );
				},
				'',
			]
		);
	}

	/**
	 * Registers the settings page CSS and JS.
	 *
	 * @param string $hook_suffix The current admin page.
	 */
	public static function register_admin_scripts( string $hook_suffix ) : void {
		if ( 'graphql_page_graphql-settings' !== $hook_suffix ) {
			return;
		}

		self::register_asset_js( 'wp-graphql-headless-login/admin-editor', 'admin' );
		wp_enqueue_style(
			'wp-graphql-headless-login/admin-styles',
			plugins_url( self::ADMIN_CSS, WPGRAPHQL_LOGIN_PLUGIN_FILE ),
			[ 'wp-components' ],
			(string) filemtime( WPGRAPHQL_LOGIN_PLUGIN_DIR . self::ADMIN_CSS ),
		);
	}

	/**
	 * Registers a JS asset.
	 *
	 * @param string $handle The asset handle.
	 * @param string $asset_name The asset name.
	 *
	 * @throws Error If the asset file is not found.
	 */
	private static function register_asset_js( string $handle, string $asset_name ) : void {
		$script_asset_path = WPGRAPHQL_LOGIN_PLUGIN_DIR . 'build/' . $asset_name . '.asset.php';
		if ( ! file_exists( $script_asset_path ) ) {
			throw new Error( __( 'You need to run `npm start` or `npm run build` for Headless Login for WPGraphQL to work.', 'wp-graphql-headless-login' ) );
		}

		$script_asset = require_once $script_asset_path;
		$js           = 'build/' . $asset_name . '.js';

		wp_register_script(
			$handle,
			plugins_url( $js, WPGRAPHQL_LOGIN_PLUGIN_FILE ),
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);
		wp_set_script_translations( $handle, 'wp-graphql-headless-login' );

		// Add settings schema to script.
		$settings = self::get_provider_settings_config();

		$settings = array_map(
			static function ( $config ) {
				return $config['show_in_rest']['schema'];
			},
			$settings
		);

		// Add meta about the secret without exposing it.
		$secret = [
			'hasKey'     => (bool) TokenManager::get_secret_key(),
			'isConstant' => defined( 'WPGRAPHQL_LOGIN_JWT_SECRET_KEY' ) && ! empty( WPGRAPHQL_LOGIN_JWT_SECRET_KEY ),
		];

		$config = [
			'secret'   => $secret,
			'settings' => $settings,
		];

		wp_add_inline_script( $handle, 'const wpGraphQLLogin = ' . wp_json_encode( $config ), 'before' );
	}

	/**
	 * Hides the JWT secret key from the REST API.
	 *
	 * @param mixed  $result Value to use for the requested setting.
	 * @param string $name   Setting name (as shown in REST API responses).
	 *
	 * @return mixed
	 */
	public static function hide_sensitive_data_from_rest( $result, $name ) {
		if ( self::$settings_prefix . 'jwt_secret_key' === $name ) {
			return '********';
		}

		return $result;
	}
}
