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

use GraphQL\Error\UserError;
use WPGraphQL\Login\Auth\ProviderConfig\ProviderConfig;
use WPGraphQL\Login\Auth\User;
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
	 * {@inheritDoc}
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
	protected function prepare_client_options() : array {
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
	 * {@inheritDoc}
	 *
	 * @return array
	 */
	public function authenticate_and_get_user_data( array $input ) {
		// Get the args from the input.
		$args = $this->prepare_mutation_input( $input );

		// Get the resource owner.
		$resource_owner = $this->get_resource_owner( $args );

		// Get the user data for the resource owner.
		return $this->get_user_data( $resource_owner );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param array $user_data The user data.
	 *
	 * @return \WP_User|false
	 */
	public function get_user_from_data( $user_data ) {
		return User::get_user_by_identity( $this->get_slug(), $user_data['subject_identity'] );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return array{code: mixed, state?: mixed}
	 *
	 * @throws UserError
	 */
	protected function prepare_mutation_input( array $input ) : array {
		if ( ! isset( $input['oauthResponse'] ) ) {
			throw new UserError(
				sprintf(
					// translators: the provider name.
					__( 'The %s provider requires the use of the `credentials` input arg.', 'wp-graphql-headless-login' ),
					$input['provider'] ?: 'OAuth2'
				)
			);
		}

		$args = [
			'code' => sanitize_text_field( $input['oauthResponse']['code'] ),
		];

		if ( isset( $input['oauthResponse']['state'] ) ) {
			$args['state'] = sanitize_text_field( $input['oauthResponse']['state'] );
		}

		return $args;
	}

	/**
	 * {@inheritDoc}
	 */
	public static function default_client_options_fields() : array {
		return array_merge(
			parent::default_client_options_fields(),
			[
				'redirectUri'  => [
					'type'        => 'String',
					'description' => __( 'The client redirect URI.', 'wp-graphql-headless-login' ),
				],
				'clientId'     => [
					'type'        => 'String',
					'description' => __( 'The client ID.', 'wp-graphql-headless-login' ),
				],
				'clientSecret' => [
					'type'        => 'String',
					'description' => __( 'The client Secret.', 'wp-graphql-headless-login' ),
				],
			]
		);
	}

	/**
	 * {@inheritDoc}
	 */
	protected static function default_client_options_schema() : array {
		return array_merge(
			parent::default_client_options_schema(),
			[
				'redirectUri'  => [
					'type'        => 'string',
					'description' => __( 'Redirect URI', 'wp-graphql-headless-login' ),
					'help'        => __( 'The frontend URL to redirect the user to after authorization.', 'wp-graphql-headless-login' ),
					'order'       => 2,
				],
				'clientId'     => [
					'type'        => 'string',
					'description' => __( 'Client ID', 'wp-graphql-headless-login' ),
					'order'       => 0,
				],
				'clientSecret' => [
					'type'        => 'string',
					'description' => __( 'Client Secret', 'wp-graphql-headless-login' ),
					'order'       => 1,
				],
			]
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public static function default_login_options_fields() : array {
		return array_merge(
			parent::default_login_options_fields(),
			[
				'createUserIfNoneExists' => [
					'type'        => 'Boolean',
					'description' => __( 'Whether to create users if none exist.', 'wp-graphql-headless-login' ),
				],
				'linkExistingUsers'      => [
					'type'        => 'Boolean',
					'description' => __( 'Whether to link existing users.', 'wp-graphql-headless-login' ),
				],
			]
		);
	}

	/**
	 * R{@inheritDoc}
	 */
	protected static function login_options_schema() : array {
		return [
			'createUserIfNoneExists' => [
				'type'        => 'boolean',
				'description' => __( 'Create new users', 'wp-graphql-headless-login' ),
				'help'        => __( 'If the user identity is not linked to an existing WordPress user, it is created. If this setting is not enabled, and if the user authenticates with an account which is not linked to an existing WordPress user, then the authentication will fail.', 'wp-graphql-headless-login' ),
				'order'       => 1,
			],
			'linkExistingUsers'      => [
				'type'        => 'boolean',
				'description' => __( 'Login existing users', 'wp-graphql-headless-login' ),
				'help'        => __( 'If a WordPress account already exists with the same identity as a newly-authenticated user, login as that user instead of generating an error.', 'wp-graphql-headless-login' ),
				'order'       => 0,
			],
		];
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
}
