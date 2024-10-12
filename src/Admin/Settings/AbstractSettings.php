<?php
/**
 * Registers a settings screen for the plugin.
 *
 * This will register the settings on the backend and the data to populate the screen.
 *
 * @package WPGraphQL\Login\Admin\Settings
 * @since @next-version
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Admin\Settings;

/**
 * Class - AbstractSettings
 *
 * @phpstan-type Setting array{
 *  description: string,
 *  label: string,
 *  type: string,
 *  conditionalLogic?: array{
 *    slug: string,
 *    operator: '==' | '!=' | '>' | '<' | '>=' | '<=',
 *    value: string|bool|int|float
 *  },
 *  controlOverrides?: array<string,mixed>,
 *  controlType?: 'jwtSecret',
 *  default?: mixed,
 *  help?: string,
 *  hidden?: bool,
 *  isAdvanced?: bool,
 *  order?: int,
 *  required?: bool,
 *  sanitize_callback: callable(mixed): mixed,
 *  validate_callback?: callable(mixed): (\WP_Error|true),
 * }
 */
abstract class AbstractSettings {
	/**
	 * The settings prefix.
	 */
	public const SETTINGS_PREFIX = 'wpgraphql_login_';

	/**
	 * The settings group.
	 */
	public const SETTINGS_GROUP = 'wpgraphql_login';

	/**
	 * The screen slug.
	 */
	abstract public static function get_slug(): string;

	/**
	 * The screen title.
	 */
	abstract public function get_title(): string;

	/**
	 * The screen description.
	 */
	abstract public function get_description(): string;

	/**
	 * The settings array to be used to register the settings.
	 *
	 * @return array<string,Setting>
	 */
	abstract public function get_config(): array;

	/**
	 * Register the settings.
	 *
	 * Should be called on the `init` action.
	 */
	public function register(): void {
		$settings_to_register = $this->get_register_setting_config();

		register_setting( self::SETTINGS_GROUP, static::get_slug(), $settings_to_register );
	}

	/**
	 * Get the config used to register the settings.
	 *
	 * @return array{
	 *  type: string,
	 *  default?: mixed,
	 *  description?: string,
	 *  default?: array<string,mixed>,
	 *  label: string,
	 *  sanitize_callback?: callable(mixed): mixed,
	 *  show_in_rest?: bool|array<string,mixed>,
	 * }
	 */
	public function get_register_setting_config(): array {
		$config = $this->get_config();

		return [
			'type'              => 'object',
			'description'       => $this->get_description(),
			'default'           => $this->get_default_values(),
			'label'             => $this->get_title(),
			'show_in_rest'      => false,
			'sanitize_callback' => static function ( $values ) use ( $config ) {
				$sanitized_values = [];

				foreach ( $values as $key => $value ) {
					$setting = $config[ $key ];

					// Sanitize the value if a callback is provided.
					$sanitized_values[ $key ] = $setting['sanitize_callback']( $value );
				}

				return $sanitized_values;
			},
		];
	}

	/**
	 * Get the config used to render the settings in the UI.
	 *
	 * @return array<string,array{
	 *  description: string,
	 *  label: string,
	 *  type: string,
	 *  conditionalLogic?: array{
	 *    slug: string,
	 *    operator: '==' | '!=' | '>' | '<' | '>=' | '<=',
	 *    value: string|bool|int|float
	 *  },
	 *  controlOverrides?: array<string,mixed>,
	 *  controlType?: string,
	 *  default?: mixed,
	 *  help?: string,
	 *  isAdvanced?: bool,
	 *  required?: bool,
	 * }>
	 */
	public function get_render_config(): array {
		$config = $this->get_config();

		// Unset the excluded keys.
		return array_map(
			static function ( $setting ) {
				$excluded_keys = [
					'sanitize_callback',
					'validate_callback',
				];

				foreach ( $excluded_keys as $key ) {
					unset( $setting[ $key ] );
				}

				return $setting;
			},
			$config
		);
	}

	/**
	 * Get the settings values.
	 *
	 * @return array<string,mixed>
	 */
	public function get_values(): array {
		$option = get_option( static::get_slug() );

		if ( empty( $option ) || ! is_array( $option ) ) {
			return [];
		}

		return $option;
	}

	/**
	 * Update the settings values.
	 *
	 * @param array<string,mixed> $values The values to update.
	 */
	public function update_values( array $values ): void {
		$existing_values = $this->get_values();

		$updated_values = array_merge( $existing_values, $values );

		update_option( static::get_slug(), $updated_values );
	}

	/**
	 * Prepares the value before saving it to the database.
	 *
	 * @param string $key The key of the setting.
	 * @param mixed  $value The value of the setting.
	 *
	 * @return mixed|\WP_Error
	 */
	public function prepare_value( string $key, $value ) {
		$config = $this->get_config();

		if ( ! isset( $config[ $key ] ) ) {
			return new \WP_Error( 'invalid_setting_key', 'Invalid setting key.' );
		}

		$setting = $config[ $key ];

		// Validate the value if a callback is provided.
		if ( isset( $setting['validate_callback'] ) ) {
			$validation_result = $setting['validate_callback']( $value );

			if ( true !== $validation_result ) {
				return new \WP_Error( 'invalid_setting_value', $validation_result );
			}
		}

		// Sanitize the value if a callback is provided.
		if ( isset( $setting['sanitize_callback'] ) ) {
			$value = $setting['sanitize_callback']( $value );
		}

		return $value;
	}

	/**
	 * Get the default values.
	 *
	 * @return array<string,mixed>
	 */
	protected function get_default_values(): array {
		$config = $this->get_config();

		$defaults = [];

		foreach ( $config as $key => $args ) {
			$defaults[ $key ] = $args['default'] ?? null;
		}

		return $defaults;
	}
}
