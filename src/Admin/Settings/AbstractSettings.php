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
		$settings_to_register = $this->get_settings_to_register();

		register_setting( self::SETTINGS_GROUP, static::get_slug(), $settings_to_register );
	}

	/**
	 * Get the settings to register.
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
	public function get_settings_to_register(): array {
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
	 * Get the settings to display.
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
	public function get_settings_to_display(): array {
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
