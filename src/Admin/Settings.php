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
use WPGraphQL\Login\Admin\Upgrade\UpgradeRegistry;
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
	 * The DOM id the admin app mounts to.
	 *
	 * @var string
	 */
	private const MOUNT_ID = 'wp-graphql-headless-login-settings';

	/**
	 * The script handle for the settings app.
	 *
	 * @var string
	 */
	private const SCRIPT_HANDLE = 'wp-graphql-headless-login/admin-editor';

	/**
	 * Registers the settings and their hooks.
	 */
	public static function init(): void {
		// Initialize the settings registry **after** WPGraphQL loads it's settings.
		add_action( 'init', [ SettingsRegistry::class, 'register_settings' ], 12 );

		add_action( 'rest_api_init', [ self::class, 'register_rest_routes' ] );
		add_action( 'init', [ self::class, 'register_provider_settings' ] );
		add_action( 'graphql_register_settings', [ self::class, 'register_settings_tab' ] );
		add_action( 'admin_enqueue_scripts', [ self::class, 'register_admin_scripts' ] );

		// The IDE loads its settings screen anywhere it's available - including the admin bar drawer.
		add_action( 'wpgraphql_ide_enqueue_script', [ self::class, 'enqueue_settings_app' ] );

		// Handle upgrades.
		UpgradeRegistry::init();
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
				'title' => __( 'Headless Login', 'wp-graphql-headless-login' ),
			]
		);

		// The WPGraphQL IDE renders the registered fields of a section, not its callback,
		// so the mount point is registered as a field to show up on both screens.
		register_graphql_settings_field(
			self::$option_group,
			[
				'name'     => 'app',
				'type'     => 'custom',
				'callback' => static function (): void {
					echo wp_kses_post( '<div id="' . self::MOUNT_ID . '"></div>' );
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

		self::enqueue_settings_app();
	}

	/**
	 * Enqueues the settings app assets.
	 *
	 * The app itself only renders once its container is in the DOM, so this is safe
	 * to enqueue on screens where the settings tab may never be opened.
	 */
	public static function enqueue_settings_app(): void {
		// The IDE also loads for anonymous visitors on its public endpoint, and the
		// localized config holds provider credentials. Mirror the REST permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( wp_script_is( self::SCRIPT_HANDLE, 'enqueued' ) ) {
			return;
		}

		self::register_asset_js( self::SCRIPT_HANDLE, 'admin' );
		wp_script_add_data( self::SCRIPT_HANDLE, 'strategy', 'defer' );
		wp_enqueue_script( self::SCRIPT_HANDLE );

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
	 * @param non-empty-string $handle The asset handle.
	 * @param string           $asset_name The asset name.
	 *
	 * @throws \Exception If the asset file is not found.
	 */
	private static function register_asset_js( string $handle, string $asset_name ): void {
		$script_asset_path = WPGRAPHQL_LOGIN_PLUGIN_DIR . 'build/' . $asset_name . '.asset.php';
		if ( ! file_exists( $script_asset_path ) ) {
			throw new \Exception( esc_html__( 'You need to run `npm start` or `npm run build` for Headless Login for WPGraphQL to work.', 'wp-graphql-headless-login' ) );
		}

		$script_asset = require_once $script_asset_path; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable
		$asset_src    = sprintf( '%s/build/%s.js', plugins_url( '', WPGRAPHQL_LOGIN_PLUGIN_FILE ), $asset_name );

		wp_register_script(
			$handle,
			$asset_src,
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

		$setting_instances = SettingsRegistry::get_all();

		$settings = [];
		foreach ( $setting_instances as $instance ) {
			$settings[ $instance::get_slug() ] = $instance->get_render_config();
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
