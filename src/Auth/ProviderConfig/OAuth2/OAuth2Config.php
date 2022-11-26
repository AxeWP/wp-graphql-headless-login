<?php
/**
 * The OAuth2 Abstract class.
 *
 * Should be extended to add support for a new OAuth2 provider.
 *
 * @package WPGraphQL\Login\Auth\ProviderConfig\OAuth2
 * @since 0.0.1
 */

namespace WPGraphQL\Login\Auth\ProviderConfig\OAuth2;

use WPGraphQL\Login\Auth\ProviderConfig\ProviderConfig;
use WPGraphQL\Login\Utils\Utils;
use WPGraphQL\Login\Vendor\League\OAuth2\Client\Provider\AbstractProvider;
use WPGraphQL\Login\Vendor\League\OAuth2\Client\Token\AccessToken;

/**
 * Class - ProviderConfig
 */
abstract class OAuth2Config extends ProviderConfig {

	/**
	 * The client options.
	 *
	 * @var array
	 */
	protected array $client_options;

	/**
	 * The provider class.
	 *
	 * @var class-string<AbstractProvider>
	 */
	protected string $provider_class;

	/**
	 * The OAuth2 provider instance.
	 *
	 * @var AbstractProvider
	 */
	protected AbstractProvider $provider;

	/**
	 * The authorization URL.
	 *
	 * @var string
	 */
	protected string $authorization_url;

	/**
	 * The Constructor.
	 *
	 * @param class-string $provider_class The OAuth2 provider class.
	 *
	 * @throws \InvalidArgumentException If the provider class is not a subclass of AbstractProvider.
	 */
	public function __construct( string $provider_class ) {
		$this->client_options = $this->prepare_client_options();

		if ( ! is_a( $provider_class, AbstractProvider::class, true ) ) {
			throw new \InvalidArgumentException( 'The provider class must extend AbstractProvider.' );
		}

		$this->provider = new $provider_class( $this->client_options );

		$this->authorization_url = $this->prepare_authorization_url( $this->client_options );

		parent::__construct();
	}

	/**
	 * {@inheritdoc}
	 */
	public static function get_type() : string {
		return 'oauth2';
	}

	/**
	 * Gets the configuration array for the provider from the saved client options.
	 *
	 * @param array<string, mixed> $settings The settings stored in the database, keyed to the expected client option property.
	 *
	 * @return array<string, mixed>
	 */
	abstract protected function get_options( array $settings ) : array;

	/**
	 * Maps the provider's user data to WP_User arguments.
	 *
	 * @param array $owner_details The Resource Owner details returned from the Authentication provider.
	 */
	abstract public function get_user_data( array $owner_details ) : array;

	/**
	 * Prepares the client options.
	 */
	public function prepare_client_options() : array {
		if ( ! isset( $this->client_options ) ) {
			$provider_settings = Utils::get_provider_settings( static::get_slug() );

			$client_options = $this->get_options( $provider_settings['clientOptions'] ?? [] );

			/**
			 * Filters the options used to configure the Authentication provider.
			 *
			 * @param array  $options The provider options stored in the database.
			 * @param string $slug The provider slug.
			 */
			$this->client_options = apply_filters( 'graphql_login_client_options', $client_options, static::get_slug() );
		}

		return $this->client_options;
	}

	/**
	 * Gets the instance of the OAuth2 Provider.
	 *
	 * @return AbstractProvider
	 */
	public function get_provider() {
		return $this->provider;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return array{code: mixed, state?: mixed}
	 */
	protected function prepare_mutation_input( array $input ) : array {
		$args = [
			'code' => sanitize_text_field( $input['oauthResponse']['code'] ),
		];

		if ( isset( $input['oauthResponse']['state'] ) ) {
			$args['state'] = sanitize_text_field( $input['oauthResponse']['state'] );
		}

		return $args;
	}

	/**
	 * Prepares the authorization url from the provider.
	 *
	 * @param array $options The options used to configure the provider.
	 */
	protected function prepare_authorization_url( array $options = [] ) : string {

		// Manually scope the options to avoid leaking sensitive data.
		$scoped_options = [];
		if ( ! empty( $options['state'] ) ) {
			$scoped_options['state'] = $options['state'];
		}
		if ( ! empty( $options['scope'] ) ) {
			$scoped_options['scope'] = $options['scope'];
		}
		if ( ! empty( $options['redirectUri'] ) ) {
			$scoped_options['redirect_uri'] = $options['redirectUri'];
		}

		return $this->provider->getAuthorizationUrl( $scoped_options );
	}

	/**
	 * Gets the authorization URL
	 */
	public function get_authorization_url() : string {
		return $this->authorization_url;
	}

	/**
	 * Gets the Resource Owner (User) data from the Provider.
	 *
	 * @param array<string, mixed> $args The arguments.
	 */
	public function get_resource_owner( array $args ) : array {
		/**
		 * Get the access token.
		 *
		 * @var AccessToken $token
		 */
		$token = $this->provider->getAccessToken( 'authorization_code', $args );

		// Get the resource owner.
		$resource_owner = $this->provider->getResourceOwner( $token );

		return $resource_owner->toArray();
	}

	/**
	 * {@inheritDoc}
	 */
	public function authenticate_and_get_user_data( array $input ) : array {
		// Get the resource owner.
		$args = $this->prepare_mutation_input( $input );

		$resource_owner = $this->get_resource_owner( $args );

		// Get the user data for the resource owner.
		$user_data = $this->get_user_data( $resource_owner );

		/**
		 * Filters the user data mapped from the Authentication provider before creating the user.
		 * Useful for mapping custom fields from the Authentication provider to the WP_User.
		 *
		 * @param array $user_data      The WordPress user data.
		 * @param array $resource_owner The the resouce owner data.
		 * @param self $provider_config An instance of the provider configuration.
		 *
		 * @since 0.0.1
		 */
		return apply_filters( 'graphql_login_mapped_user_data', $user_data, $resource_owner, $this );
	}
}
