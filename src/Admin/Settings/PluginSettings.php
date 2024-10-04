<?php
/**
 * Registers the Plugin Settings
 *
 * @package WPGraphQL\Login\Admin\Settings
 * @since 0.0.6
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Admin\Settings;

/**
 * Class PluginSettings
 */
class PluginSettings extends AbstractSettings {
	/**
	 * {@inheritDoc}
	 */
	public static function get_slug(): string {
		return self::SETTINGS_PREFIX . 'settings';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_title(): string {
		return __( 'Plugin Settings', 'wp-graphql-headless-login' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_description(): string {
		return __( 'Miscellaneous settings for the plugin.', 'wp-graphql-headless-login' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_config(): array {
		return [
			// Admin Display Settings.
			'show_advanced_settings'    => [
				'description'       => __( 'Show advanced settings in the admin.', 'wp-graphql-headless-login' ),
				'label'             => __( 'Show Advanced Settings', 'wp-graphql-headless-login' ),
				'type'              => 'boolean',
				'default'           => false,
				'hidden'            => true,
				'required'          => false,
				'sanitize_callback' => 'rest_sanitize_boolean',
			],
			// Delete Data on Deactivate.
			'delete_data_on_deactivate' => [
				'description'       => __( 'Delete all data on plugin deactivation.', 'wp-graphql-headless-login' ),
				'label'             => __( 'Delete plugin data on deactivate.', 'wp-graphql-headless-login' ),
				'type'              => 'boolean',
				'isAdvanced'        => false,
				'default'           => false,
				'help'              => __( 'When selected, all plugin data will be deleted when the plugin is deactivated, including client configurations. Mote: No user meta will be deleted.', 'wp-graphql-headless-login' ),
				'order'             => 1,
				'required'          => false,
				'sanitize_callback' => 'rest_sanitize_boolean',
			],
			// The JWT Secret.
			'jwt_secret_key'            => [
				'default'           => false,
				'description'       => __( 'The JWT Secret Key.', 'wp-graphql-headless-login' ),
				'label'             => __( 'Regenerate JWT Secret', 'wp-graphql-headless-login' ),
				'help'              => __(
					'The JWT Secret is used to sign the JWT tokens that are used to authenticate requests to the GraphQL API. Changing this secret will invalidate all previously-authenticated requests.',
					'wp-graphql-headless-login'
				),
				'isAdvanced'        => true,
				'type'              => 'string',
				'controlType'       => 'jwtSecret',
				'sanitize_callback' => 'sanitize_text_field',
			],
		];
	}
}
