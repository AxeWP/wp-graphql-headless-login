<?php
/**
 * The ProviderConfig Abstract class.
 *
 * Should be extended to add support for a new Authentication provider.
 *
 * @package WPGraphQL\Login\Auth\ProviderConfig
 * @since 0.0.1
 */

namespace WPGraphQL\Login\Auth\ProviderConfig;

/**
 * Class - ProviderConfig
 */
abstract class ProviderConfig {
	use ProviderConfigStaticTrait;

	/**
	 * The constructor
	 */
	public function __construct() {
		/**
		 * Fires after the provider is initialized.
		 *
		 * @param string $slug            The provider slug.
		 * @param self   $provider_config The ProviderConfig static class.
		 */
		do_action( 'graphql_login_after_provider_init', static::get_slug(), $this );
	}

	/**
	 * Get the provider type.
	 *
	 * E.g. 'oauth2', 'saml', etc .
	 */
	abstract public static function get_type() : string;

	/**
	 * Get the provider name.
	 *
	 * E.g. 'Facebook'.
	 */
	abstract public static function get_name() : string;

	/**
	 * Get the provider slug.
	 *
	 * E.g. 'facebook'.
	 */
	abstract public static function get_slug() : string;

	/**
	 * Authenticates the user based on the input and returns a valid WP_User.
	 *
	 * @param array $input  The mutation input.
	 */
	abstract public function authenticate_and_get_user_data( array $input ) : array;

	/**
	 * Process and validate the input data passed to the GraphQL mutation.
	 *
	 * @param array $input The mutation input.
	 */
	abstract protected function prepare_mutation_input( array $input ) : array;

	/**
	 * Gets the WPGraphQL fields config for the provider settings.
	 *
	 * Should probably be overwritten by the child ProviderConfig.
	 */
	protected static function client_options_fields() : array {
		return [];
	}

	/**
	 * Returns the schema properties for the client options.
	 *
	 * Adds the optional 'help' and `required' property key for use on the frontend. This will be stripped when registering the setting.
	 *
	 * Should probably be overwritten by the child ProviderConfig.
	 *
	 * @see https://developer.wordpress.org/rest-api/extending-the-rest-api/schema
	 */
	protected static function client_options_schema() : array {
		return [];
	}

	/**
	 * Gets the WPGraphQL fields config for the provider settings.
	 *
	 * Should probably be overwritten by the child ProviderConfig.
	 */
	protected static function login_options_fields() : array {
		return [
			'linkExistingUsers' => [
				'type'        => 'Boolean',
				'description' => __( 'Whether to link existing users.', 'wp-graphql-headless-login' ),
			],
		];
	}

	/**
	 * Returns the schema properties for the provider settings.
	 *
	 * Adds the optional 'help' property key for use on the frontend. This will be stripped when registering the setting.
	 *
	 * Should probably be overwritten by the child ProviderConfig.
	 *
	 * @see https://developer.wordpress.org/rest-api/extending-the-rest-api/schema
	 */
	protected static function login_options_schema() : array {
		return [
			'linkExistingUsers' => [
				'type'        => 'boolean',
				'description' => __( 'Login existing users', 'wp-graphql-headless-login' ),
				'help'        => __( 'If a WordPress account already exists with the same identity as a newly-authenticated user, login as that user instead of generating an error.', 'wp-graphql-headless-login' ),
				'order'       => 0,
			],
		];
	}
}
