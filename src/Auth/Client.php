<?php
/**
 * The Authentication Client.
 *
 * @package WPGraphQL\Login\Auth
 * @since 0.0.1
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Auth;

use WPGraphQL\Login\Auth\ProviderConfig\ProviderConfig;
use WPGraphQL\Login\Utils\Utils;

/**
 * Class - Client
 */
class Client {
	/**
	 * The client config.
	 *
	 * @var array<string,mixed>
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
	 * @var \WPGraphQL\Login\Auth\ProviderConfig\ProviderConfig
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
		 * @param \WPGraphQL\Login\Auth\ProviderConfig\ProviderConfig $provider_config The provider configurator.
		 * @param \WPGraphQL\Login\Auth\Client $client The Client instance.
		 */
		do_action( 'graphql_login_client_init', $this->slug, $this->config, $this->provider_configurator, $this );
	}

	/**
	 * Gets the provider slug.
	 */
	public function get_provider_slug(): string {
		return $this->slug;
	}

	/**
	 * Gets the provider name.
	 */
	public function get_provider_name(): string {
		return $this->name;
	}

	/**
	 * Gets the provider type.
	 */
	public function get_provider_type(): string {
		return $this->type;
	}

	/**
	 * Gets the instance of the ProviderConfig class.
	 */
	public function get_provider_configurator(): ProviderConfig {
		return $this->provider_configurator;
	}

	/**
	 * Returns the config used to configure the client.
	 *
	 * @return array<string,mixed>
	 */
	public function get_config(): array {
		return $this->config;
	}

	/**
	 * Gets the authorization url for the provider's server.
	 *
	 * @uses ProviderConfig::get_authorization_url()
	 */
	public function get_authorization_url(): ?string {
		if ( method_exists( $this->provider_configurator, 'get_authorization_url' ) ) {
			return $this->provider_configurator->get_authorization_url( $this->config );
		}

		return null;
	}

	/**
	 * Uses the provider config to authenticate and return the user.
	 *
	 * @param array<string,mixed> $input The mutation input data.
	 *
	 * @return array<string,mixed>|\WP_User|\WP_Error|false
	 */
	public function authenticate_and_get_user_data( array $input ) {
		/**
		 * Fires before the user is authenticated.
		 *
		 * @param string                                              $slug            The provider slug.
		 * @param array                                               $input           The mutation input data.
		 * @param array                                               $settings        The client settings.
		 * @param \WPGraphQL\Login\Auth\ProviderConfig\ProviderConfig $provider_config The provider config.
		 * @param \WPGraphQL\Login\Auth\Client                        $client          The Client instance.
		 */
		do_action( 'graphql_login_before_authenticate', $this->slug, $input, $this->config, $this->provider_configurator, $this );

		$user_data = $this->provider_configurator->authenticate_and_get_user_data( $input );

		/**
		 * Filters the user data returned from the Authentication provider.
		 *
		 * @param array<string,mixed>|\WP_User|\WP_Error|false        $user_data       The user data.
		 * @param string                                              $slug            The provider slug.
		 * @param array                                               $input           The mutation input data.
		 * @param array                                               $settings        The client settings.
		 * @param \WPGraphQL\Login\Auth\ProviderConfig\ProviderConfig $provider_config The provider config.
		 * @param \WPGraphQL\Login\Auth\Client                        $client          The Client instance.
		 */
		$user_data = apply_filters( 'graphql_login_authenticated_user_data', $user_data, $this->slug, $input, $this->config, $this->provider_configurator, $this );

		/**
		 * Fires when the user is authenticated.
		 *
		 * @param array<string,mixed>|\WP_User|\WP_Error|false        $user_data       The user data.
		 * @param string                                              $slug            The provider slug.
		 * @param array                                               $input           The mutation input data.
		 * @param array                                               $settings        The client settings.
		 * @param \WPGraphQL\Login\Auth\ProviderConfig\ProviderConfig $provider_config The provider config.
		 * @param \WPGraphQL\Login\Auth\Client                        $client          The Client instance.
		 */
		do_action( 'graphql_login_after_authenticate', $user_data, $this->slug, $input, $this->config, $this->provider_configurator, $this );

		return $user_data;
	}

	/**
	 * Uses the authenticated user data to return the user.
	 *
	 * @param array<string,mixed>|\WP_User $data the user data.
	 *
	 * @return \WP_User|false
	 */
	public function get_user_from_data( $data ) {
		/**
		 * Shortcircuits the user matching logic, allowing you to provide your own logic for matching the user from the provider user data.
		 * If null is returned, the default matching logic will be used.
		 *
		 * @param \WP_User|false|null                                 $pre_get_user    The user matched from the data. If null, the default matching logic will be used.
		 * @param array<string,mixed>|\WP_User                        $data            The user data from the provider.
		 * @param string                                              $slug            The provider slug.
		 * @param array                                               $settings        The client settings.
		 * @param \WPGraphQL\Login\Auth\ProviderConfig\ProviderConfig $provider_config The provider config.
		 * @param \WPGraphQL\Login\Auth\Client                        $client          The Client instance.
		 */
		$user = apply_filters( 'graphql_login_pre_get_user_from_data', null, $data, $this->slug, $this->config, $this->provider_configurator, $this );

		if ( null === $user ) {
			$user = $this->provider_configurator->get_user_from_data( $data );
		}

		/**
		 * Fires when the user is matched from the data.
		 * Useful for updating custom meta fields from the provider.
		 *
		 * @param \WP_User|false                                      $user            The user matched from the data.
		 * @param array<string,mixed>|\WP_User                        $user_data            The user data from the provider.
		 * @param string                                              $slug            The provider slug.
		 * @param array                                               $settings        The client settings.
		 * @param \WPGraphQL\Login\Auth\ProviderConfig\ProviderConfig $provider_config The provider config.
		 * @param \WPGraphQL\Login\Auth\Client                        $client          The Client instance.
		 */
		do_action( 'graphql_login_get_user_from_data', $user, $data, $this->slug, $this->config, $this->provider_configurator, $this );

		return $user;
	}

	/**
	 * Maybe creates a user from the provided user data.
	 *
	 * @param array<string,mixed>|mixed $user_data The user data.
	 *
	 * @return \WP_User|\WP_Error|false
	 */
	public function maybe_create_user( $user_data ) {
		/**
		 * Deprecated filter. Use `graphql_login_create_user_data` instead.
		 *
		 * @param array $user_data       The WordPress user data.
		 * @param self  $provider_config An instance of the provider configuration.
		 *
		 * @since 0.0.1
		 * @deprecated 0.1.4
		 */
		$user_data = apply_filters_deprecated(
			'graphql_login_mapped_user_data',
			[ $user_data, $this ],
			'0.1.4',
			'graphql_login_create_user_data'
		);

		/**
		 * Filters the user data mapped from the Authentication provider before creating the user.
		 * Useful for mapping custom fields from the Authentication provider to the WP_User.
		 *
		 * @param array $user_data       The WordPress user data.
		 * @param self  $provider_config An instance of the provider configuration.
		 *
		 * @since 0.1.4
		 */
		$user_data = apply_filters( 'graphql_login_create_user_data', $user_data, $this );

		if ( ! is_array( $user_data ) ) {
			return false;
		}

		return User::maybe_create_user( $this, $user_data );
	}
}
