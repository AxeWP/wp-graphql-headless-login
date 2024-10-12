<?php
/**
 * Registers plugin settings to the backend.
 *
 * @package WPGraphQL\Login\Admin
 * @since 0.0.1
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Admin;

use WPGraphQL\Login\Admin\Settings\ProviderSettings;
use WPGraphQL\Login\Admin\Settings\RestController;
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
	 * The admin.css file
	 *
	 * @var string
	 */
	private const ADMIN_CSS = 'build/admin.css';

	/**
	 * {@inheritDoc}
	 */
	public static function init(): void {
		// Initialize the settings registry.
		add_action( 'init', [ SettingsRegistry::class, 'register_settings' ] );

		add_action( 'rest_api_init', [ self::class, 'register_rest_routes' ] );
		add_action( 'init', [ self::class, 'register_provider_settings' ] );
		add_action( 'graphql_register_settings', [ self::class, 'register_settings_tab' ] );
		add_action( 'admin_enqueue_scripts', [ self::class, 'register_admin_scripts' ] );
	}

	/**
	 * Registers the REST API routes for the settings.
	 */
	public static function register_rest_routes(): void {
		$controller = new RestController();
		$controller->register_routes();
	}

	/**
	 * Register the settings to WordPress.
	 */
	public static function register_provider_settings(): void {
		$settings = ProviderSettings::get_settings_args();
		foreach ( $settings as $setting_name => $args ) {
			register_setting(
				self::$option_group,
				$setting_name,
				$args
			);
		}
	}

	/**
	 * Register the Settings Tab to WPGraphQL.
	 */
	public static function register_settings_tab(): void {
		register_graphql_settings_section(
			self::$option_group,
			[
				'title'    => __( 'Headless Login', 'wp-graphql-headless-login' ),
				'callback' => static function (): void {
					wp_enqueue_script( 'wp-graphql-headless-login/admin-editor' );
					wp_enqueue_script( 'wp-graphql-headless-login/admin-styles' );

					echo wp_kses_post( '<div id="wp-graphql-headless-login-settings"></div>' );
				},
			]
		);
	}

	/**
	 * Registers the settings page CSS and JS.
	 *
	 * @param string $hook_suffix The current admin page.
	 */
	public static function register_admin_scripts( string $hook_suffix ): void {
		if ( 'graphql_page_graphql-settings' !== $hook_suffix ) {
			return;
		}

		// Maybe load react-jsx-runtime polyfill.
		if ( ! wp_script_is( 'react-jsx-runtime', 'registered' ) ) {
			wp_register_script(
				'react-jsx-runtime',
				plugins_url( 'build/react-jsx-runtime.js', WPGRAPHQL_LOGIN_PLUGIN_FILE ),
				[],
				(string) filemtime( WPGRAPHQL_LOGIN_PLUGIN_DIR . 'build/react-jsx-runtime.js' ),
				true
			);
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
	 * @throws \Exception If the asset file is not found.
	 */
	private static function register_asset_js( string $handle, string $asset_name ): void {
		$script_asset_path = WPGRAPHQL_LOGIN_PLUGIN_DIR . 'build/' . $asset_name . '.asset.php';
		if ( ! file_exists( $script_asset_path ) ) {
			throw new \Exception( esc_html__( 'You need to run `npm start` or `npm run build` for Headless Login for WPGraphQL to work.', 'wp-graphql-headless-login' ) );
		}

		$script_asset = require_once $script_asset_path; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable
		$js           = 'build/' . $asset_name . '.js';

		wp_register_script(
			$handle,
			plugins_url( $js, WPGRAPHQL_LOGIN_PLUGIN_FILE ),
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);
		wp_set_script_translations( $handle, 'wp-graphql-headless-login' );

		$config = self::get_settings_data();

		wp_add_inline_script( $handle, 'const wpGraphQLLogin = ' . wp_json_encode( $config ), 'before' );
	}

	/**
	 * Gets the plugin setting data to pass to the JS.
	 *
	 * @return array<string,mixed>
	 */
	private static function get_settings_data(): array {
		// Add meta about the secret without exposing it.
		$secret = [
			'hasKey'     => (bool) TokenManager::get_secret_key(),
			'isConstant' => defined( 'WPGRAPHQL_LOGIN_JWT_SECRET_KEY' ) && ! empty( WPGRAPHQL_LOGIN_JWT_SECRET_KEY ),
		];

		$plugin_registry = SettingsRegistry::get_all();

		$settings = [];
		foreach ( $plugin_registry as $setting ) {
			$settings[ $setting::get_slug() ] = $setting->get_settings_to_display();
		}

		return [
			'secret'   => $secret,
			'settings' => array_merge(
				$settings,
				[
					'providers' => ProviderSettings::get_config(),
				],
			),
			'nonce'    => wp_create_nonce( 'wp_graphql_settings' ),
		];
	}
}
