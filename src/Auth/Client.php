<?php
/**
 * The Authentication Client.
 *
 * @package WPGraphQL\Login\Auth
 * @since 0.0.1
 */

namespace WPGraphQL\Login\Auth;

use WP_Error;
use WPGraphQL\Login\Auth\ProviderConfig\ProviderConfig;
use WPGraphQL\Login\Utils\Utils;

/**
 * Class - Client
 */
class Client {
	/**
	 * The client config.
	 *
	 * @var array
	 */
	private array $config;

	/**
	 * The provider name.
	 *
	 * @var string
	 */
	private string $name;

	/**
	 * The instance of the ProviderConfig class.
	 *
	 * @var ProviderConfig
	 */
	private $provider_configurator;

	/**
	 * The provider slug.
	 *
	 * @var string
	 */
	private string $slug;

	/**
	 * The provider type.
	 *
	 * @var string
	 */
	private string $type;


	/**
	 * The class constructor.
	 *
	 * @param string $slug The slug of the provider config.
	 */
	public function __construct( string $slug ) {
		$this->slug                  = $slug;
		$this->provider_configurator = ProviderRegistry::get_instance()->get_provider_config( $this->slug );

		$this->name   = $this->provider_configurator::get_name();
		$this->type   = $this->provider_configurator::get_type();
		$this->config = Utils::get_provider_settings( $this->slug );

		/**
		 * Fires after the Client is instantiated.
		 *
		 * @param string         $slug            The slug of the provider config.
		 * @param array          $settings        The client settings.
		 * @param ProviderConfig $provider_config The provider configurator.
		 * @param Client         $client          The Client instance.
		 */
		do_action( 'graphql_login_client_init', $this->slug, $this->config, $this->provider_configurator, $this );
	}

	/**
	 * Gets the provider slug.
	 */
	public function get_provider_slug() : string {
		return $this->slug;
	}

	/**
	 * Gets the provider name.
	 */
	public function get_provider_name() : string {
		return $this->name;
	}

	/**
	 * Gets the provider type.
	 */
	public function get_provider_type() : string {
		return $this->type;
	}

	/**
	 * Gets the instance of the ProviderConfig class.
	 */
	public function get_provider_configurator() : ProviderConfig {
		return $this->provider_configurator;
	}

	/**
	 * Returns the config used to configure the client.
	 */
	public function get_config() : array {
		return $this->config;
	}

	/**
	 * Gets the authorization url for the provider's server.
	 *
	 * @uses ProviderConfig::get_authorization_url()
	 */
	public function get_authorization_url() : ?string {
		if ( method_exists( $this->provider_configurator, 'get_authorization_url' ) ) {
			return $this->provider_configurator->get_authorization_url( $this->config );
		}

		return null;
	}

	/**
	 * Uses the provider config to authenticate and return the user.
	 *
	 * @param array $input the input data.
	 *
	 * @return array|\WP_User|\WP_Error|false
	 */
	public function authenticate_and_get_user_data( array $input ) {
		/**
		 * Fires before the user is authenticated.
		 *
		 * @param string         $slug            The provider slug.
		 * @param array          $input           The mutation input data.
		 * @param array          $settings        The client settings.
		 * @param ProviderConfig $provider_config The provider config.
		 * @param Client         $client          The Client instance.
		 */
		do_action( 'graphql_login_before_authenticate', $this->slug, $input, $this->config, $this->provider_configurator, $this );

		return $this->provider_configurator->authenticate_and_get_user_data( $input );
	}

	/**
	 * Uses the authenticated user data to return the user.
	 *
	 * @param array|\WP_User $data the user data data.
	 *
	 * @return \WP_User|\WP_Error|false
	 */
	public function get_user_from_data( $data ) {
		return $this->provider_configurator->get_user_from_data( $data );
	}

	/**
	 * Maybe creates a user from the provided user data.
	 *
	 * @param array|mixed $user_data The user data.
	 *
	 * @return \WP_User|WP_Error|false
	 */
	public function maybe_create_user( $user_data ) {
		/**
		 * Filters the user data mapped from the Authentication provider before creating the user.
		 * Useful for mapping custom fields from the Authentication provider to the WP_User.
		 *
		 * @param array $user_data      The WordPress user data.
		 * @param self $provider_config An instance of the provider configuration.
		 *
		 * @since 0.0.1
		 */
		$user_data = apply_filters( 'graphql_login_mapped_user_data', $user_data, $this );

		if ( ! is_array( $user_data ) ) {
			return false;
		}

		return User::maybe_create_user( $this, $user_data );
	}
}
