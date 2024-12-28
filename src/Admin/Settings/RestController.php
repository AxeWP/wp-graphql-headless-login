<?php
/**
 * The Rest Controller for the plugin settings
 *
 * @package WPGraphQL\Login\Admin\Settings
 * @since 0.4.0
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Admin\Settings;

use WPGraphQL\Login\Admin\SettingsRegistry;

/**
 * Class RestController
 */
class RestController extends \WP_REST_Controller {
	/**
	 * The namespace for the settings.
	 */
	public const NAMESPACE = 'wp-graphql-login/v1';

	/**
	 * The rest base for the settings.
	 */
	public const REST_BASE = 'settings';

	/**
	 * {@inheritDoc}
	 */
	public function register_routes(): void {
		// Get route.
		register_rest_route(
			self::NAMESPACE,
			'/' . self::REST_BASE,
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_items' ],
				'permission_callback' => [ $this, 'get_item_permissions_check' ],
			]
		);

		// Post route.
		register_rest_route(
			self::NAMESPACE,
			'/' . self::REST_BASE,
			[
				'methods'             => \WP_REST_Server::EDITABLE,
				'args'                => $this->update_item_args(),
				'callback'            => [ $this, 'update_item' ],
				'permission_callback' => [ $this, 'get_item_permissions_check' ],
			]
		);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param \WP_REST_Request<mixed[]> $request The request object.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_items( $request ) {
		$values = $this->get_all_settings_values();

		$response = new \WP_REST_Response( $values, 200 );

		return rest_ensure_response( $response );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param \WP_REST_Request<mixed[]> $request The request object.
	 */
	public function get_item_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'Sorry, you are not allowed to access settings.', 'wp-graphql-headless-login' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}

		return true;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param \WP_REST_Request<array{slug:string,values:array<string,mixed>}> $request The request object.
	 *
	 * @return \WP_REST_Response
	 */
	public function update_item( $request ) {
		/** @var array<string,mixed> $values */
		$values = $request->get_param( 'values' );
		$slug   = (string) $request->get_param( 'slug' );

		/** @var \WPGraphQL\Login\Admin\Settings\AbstractSettings $setting */
		$setting = SettingsRegistry::get( $slug );

		$setting->update_values( $values );

		$response = new \WP_REST_Response( $this->get_all_settings_values(), 200 );

		return rest_ensure_response( $response );
	}

	/**
	 * Get the arguments for the post route.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function update_item_args(): array {
		return [
			'slug'   => [
				'type'              => 'string',
				'required'          => true,
				'enum'              => $this->get_allowed_slugs(),
				'sanitize_callback' => 'rest_sanitize_request_arg',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'values' => [
				'type'              => 'object',
				'required'          => true,
				'sanitize_callback' => static function ( $param, $request ) {
					$slug = $request->get_param( 'slug' );

					/** @var \WPGraphQL\Login\Admin\Settings\AbstractSettings $setting */
					$setting = SettingsRegistry::get( $slug );

					// Sanitize from the setting schema.
					$config = $setting->get_config();

					$sanitized_values = [];

					foreach ( $param as $key => $value ) {
						// Skip if the key is not in the config.
						if ( ! isset( $config[ $key ] ) ) {
							continue;
						}

						$sanitized_values[ $key ] = $config[ $key ]['sanitize_callback']( $value );
					}

					return $sanitized_values;
				},
				'validate_callback' => static function ( $param, $request ) {
					// Bail if the values are not an array.
					if ( ! is_array( $param ) ) {
						return new \WP_Error(
							'rest_invalid_values',
							__( 'Invalid setting values.', 'wp-graphql-headless-login' ),
							[ 'status' => 400 ]
						);
					}

					// We cast the slug in case it hasn't been sanitized yet.
					$slug = (string) $request->get_param( 'slug' );

					$setting = ! empty( $slug ) ? SettingsRegistry::get( $slug ) : null;

					// Bail if the setting is not found.
					if ( ! $setting ) {
						return self::get_invalid_setting_error( $slug );
					}

					// Validate from the setting schema.
					$config = $setting->get_config();

					// Check if the values are valid.
					foreach ( $param as $key => $value ) {
						if ( ! isset( $config[ $key ] ) ) {
							return self::get_invalid_setting_error( $key );
						}

						$valid = self::validate_setting_value( $value, $config[ $key ] );

						if ( is_wp_error( $valid ) ) {
							return $valid;
						}
					}

					return true;
				},
			],
		];
	}

	/**
	 * Gets the allowed slug values.
	 *
	 * @return string[]
	 */
	private static function get_allowed_slugs(): array {
		$settings = SettingsRegistry::get_all();

		return array_keys( $settings );
	}

	/**
	 * Get all setting values.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	private function get_all_settings_values(): array {
		$settings = SettingsRegistry::get_all();

		$values = [];

		foreach ( $settings as $setting ) {
			$unsanitize_values = $setting->get_values();

			$values[ $setting::get_slug() ] = $this->sanitize_private_data( $unsanitize_values );
		}

		return $values;
	}

	/**
	 * Sanitize private data from the settings.
	 *
	 * @param array<string,mixed> $values The values to sanitize.
	 *
	 * @return array<string,mixed>
	 */
	private function sanitize_private_data( array $values ): array {
		// Hide the JWT secret key.
		if ( isset( $values['jwt_secret_key'] ) ) {
			$values['jwt_secret_key'] = '********';
		}

		return $values;
	}

	/**
	 * Gets a WP_Error object for an invalid setting.
	 *
	 * @param string $slug The setting slug.
	 */
	private static function get_invalid_setting_error( string $slug ): \WP_Error {
		return new \WP_Error(
			'rest_invalid_setting',
			sprintf(
				// translators: %s: The setting slug.
				__( 'Invalid setting: %s', 'wp-graphql-headless-login' ),
				esc_html( $slug )
			),
			[ 'status' => 400 ]
		);
	}

	/**
	 * Validates a setting value.
	 *
	 * @param mixed               $value The value to validate.
	 * @param array<string,mixed> $setting The setting schema.
	 *
	 * @return bool|\WP_Error
	 */
	private static function validate_setting_value( $value, array $setting ) {
		// Check if the value is required.
		if ( ! isset( $value ) && $setting['required'] ) {
			return new \WP_Error(
				'rest_missing_required_value',
				__( 'The setting value is required.', 'wp-graphql-headless-login' ),
				[ 'status' => 400 ]
			);
		}

		// Validate the value if a callback is provided.
		if ( isset( $setting['validate_callback'] ) ) {
			$valid = $setting['validate_callback']( $value );

			if ( is_wp_error( $valid ) ) {
				return $valid;
			}
		}

		return true;
	}
}
