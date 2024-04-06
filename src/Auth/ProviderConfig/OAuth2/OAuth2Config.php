<?php
/**
 * The OAuth2 Abstract class.
 *
 * Should be extended to add support for a new OAuth2 provider.
 *
 * @package WPGraphQL\Login\Auth\ProviderConfig\OAuth2
 * @since 0.0.1
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Auth\ProviderConfig\OAuth2;

use GraphQL\Error\UserError;
use WPGraphQL\Login\Auth\ProviderConfig\ProviderConfig;
use WPGraphQL\Login\Auth\User;
use WPGraphQL\Login\Utils\Utils;
use WPGraphQL\Login\Vendor\League\OAuth2\Client\Provider\AbstractProvider;
use WP_Error;

/**
 * Class - ProviderConfig
 */
abstract class OAuth2Config extends ProviderConfig {
	/**
	 * The client options.
	 *
	 * @var array<string,mixed>
	 */
	protected array $client_options;

	/**
	 * The provider class.
	 *
	 * @var class-string<\WPGraphQL\Login\Vendor\League\OAuth2\Client\Provider\AbstractProvider>
	 */
	protected string $provider_class;

	/**
	 * The OAuth2 provider instance.
	 *
	 * @var \WPGraphQL\Login\Vendor\League\OAuth2\Client\Provider\AbstractProvider
	 */
	protected $provider;

	/**
	 * The authorization URL.
	 *
	 * @var string
	 */
	protected string $authorization_url;

	/**
	 * {@inheritDoc}
	 *
	 * @param string $provider_class The OAuth2 provider class.
	 *
	 * @throws \InvalidArgumentException If the provider class is not a subclass of AbstractProvider.
	 */
	public function __construct( string $provider_class ) {
		$this->client_options = $this->prepare_client_options();

		if ( ! is_a( $provider_class, AbstractProvider::class, true ) && ! is_a( $provider_class, 'League\OAuth2\Client\Provider\AbstractProvider', true ) ) { // Check for the prefixed and unprefixed class names.
			throw new \InvalidArgumentException(
				sprintf(
					// translators: the provider class name.
					esc_html__( 'The provider class must extend AbstractProvider. %s does not', 'wp-graphql-headless-login' ),
					esc_html( $provider_class )
				)
			);
		}

		/** @var class-string<\WPGraphQL\Login\Vendor\League\OAuth2\Client\Provider\AbstractProvider> $provider_class */
		$this->provider = new $provider_class( $this->client_options );

		$this->authorization_url = $this->prepare_authorization_url( $this->client_options );

		parent::__construct();
	}

	/**
	 * {@inheritdoc}
	 */
	public static function get_type(): string {
		return 'oauth2';
	}

	/**
	 * Gets the configuration array for the provider from the saved client options.
	 *
	 * @param array<string,mixed> $settings The settings stored in the database, keyed to the expected client option property.
	 *
	 * @return array<string,mixed>
	 */
	abstract protected function get_options( array $settings ): array;

	/**
	 * Maps the provider's user data to WP_User arguments.
	 *
	 * @param array<string,mixed> $owner_details The Resource Owner details returned from the Authentication provider.
	 *
	 * @return array<string,mixed>
	 */
	abstract public function get_user_data( array $owner_details ): array;

	/**
	 * Prepares the client options.
	 *
	 * @return array<string,mixed>
	 */
	protected function prepare_client_options(): array {
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
	 * @return \WPGraphQL\Login\Vendor\League\OAuth2\Client\Provider\AbstractProvider
	 */
	public function get_provider() {
		return $this->provider;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array<string,mixed>|\WP_Error
	 */
	public function authenticate_and_get_user_data( array $input ) {
		// Start the session.
		if ( ! session_id() && ! headers_sent() ) { // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.session_session_id
			session_start(); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.session_session_start
		}

		// Get the args from the input.
		$args = $this->prepare_mutation_input( $input );

		// Test if the state returned from the provider matches the state stored in the session.
		if ( isset( $_SESSION['oauth2state'] ) && ! empty( $args['state'] ) && $args['state'] !== $_SESSION['oauth2state'] ) { // phpcs:ignore WordPressVIPMinimum.Variables.RestrictedVariables.session___SESSION
			return new WP_Error(
				'invalid-oauth2-state',
				sprintf(
					// translators: the provider name.
					__( 'The state returned from the %s response does not match.', 'wp-graphql-headless-login' ),
					$input['provider'] ?: 'OAuth2'
				)
			);
		}

		// Get the resource owner.
		$resource_owner = $this->get_resource_owner( $args );

		// Get the user data for the resource owner.
		return $this->get_user_data( $resource_owner );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param array<string,mixed> $user_data The user data.
	 *
	 * @return \WP_User|false
	 */
	public function get_user_from_data( $user_data ) {
		if ( empty( $user_data['subject_identity'] ) ) {
			return false;
		}

		return User::get_user_by_identity( $this->get_slug(), $user_data['subject_identity'] );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return array{code: mixed, state?: mixed}
	 *
	 * @throws \GraphQL\Error\UserError
	 */
	protected function prepare_mutation_input( array $input ): array {
		if ( ! isset( $input['oauthResponse'] ) ) {
			throw new UserError(
				sprintf(
					// translators: the provider name.
					esc_html__( 'The %s provider requires the use of the `oauthResponse` input arg.', 'wp-graphql-headless-login' ),
					esc_html( ! empty( $input['provider'] ) ? $input['provider'] : 'OAuth2' )
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
	public static function default_client_options_fields(): array {
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
	protected static function default_client_options_schema(): array {
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
	public static function default_login_options_fields(): array {
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
	 * {@inheritDoc}
	 */
	protected static function login_options_schema(): array {
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
	 * @param array<string,mixed> $options The options used to configure the provider.
	 */
	protected function prepare_authorization_url( array $options = [] ): string {

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
	public function get_authorization_url(): string {
		return $this->authorization_url;
	}

	/**
	 * Gets the Resource Owner (User) data from the Provider.
	 *
	 * @param array<string,mixed> $args The arguments.
	 *
	 * @return array<string,mixed> The resource owner data.
	 */
	public function get_resource_owner( array $args ): array {
		/**
		 * Get the access token.
		 *
		 * @var \WPGraphQL\Login\Vendor\League\OAuth2\Client\Token\AccessToken $token
		 */
		$token = $this->provider->getAccessToken( 'authorization_code', $args );

		// Get the resource owner.
		$resource_owner = $this->provider->getResourceOwner( $token );

		return $resource_owner->toArray();
	}
}
