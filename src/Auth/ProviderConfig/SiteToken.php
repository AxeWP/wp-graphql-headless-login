<?php
/**
 * The Site Token provider class.
 *
 * @package WPGraphQL\Login\Auth\ProviderConfig
 * @since @todo
 */

namespace WPGraphQL\Login\Auth\ProviderConfig;

use GraphQL\Error\UserError;
use WPGraphQL\Login\Auth\User;
use WPGraphQL\Login\Utils\Utils;

/**
 * Class - SiteToken
 */
class SiteToken extends ProviderConfig {
	/**
	 * The provider options as stored in the database.
	 *
	 * @var array
	 */
	protected array $options;

	/**
	 * {@inheritDoc}
	 */
	public function __construct() {
		$this->options = Utils::get_provider_settings( static::get_slug() );

		parent::__construct();
	}

	/**
	 * {@inheritdoc}
	 */
	public static function get_type() : string {
		return 'siteToken';
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_name() : string {
		return __( 'Site Token', 'wp-graphql-headless-login' );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_slug() : string {
		return 'siteToken';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array|\WP_Error
	 */
	public function authenticate_and_get_user_data( array $input ) {
		// Get the args from the input.
		$args = $this->prepare_mutation_input( $input );

		// convert to valid $_SERVER key.
		$header_key = ! empty( $this->options['clientOptions']['headerKey'] ) ? strtoupper( str_replace( '-', '_', $this->options['clientOptions']['headerKey'] ) ) : '';

		if ( empty( $header_key ) ) {
			return new \WP_Error(
				'graphql-headless-login-missing-header-key',
				__( 'Header key for site token authentication is not defined.', 'wp-graphql-headless-login' )
			);
		}

		$secret = $_SERVER[ 'HTTP_' . $header_key ] ?? '';

		if ( empty( $secret ) ) {
			return new \WP_Error(
				'graphql-headless-login-missing-header-token',
				__( 'Missing site token in custom header.', 'wp-graphql-headless-login' )
			);
		}

		if ( ! isset( $this->options['clientOptions']['secretKey'] ) || $secret !== $this->options['clientOptions']['secretKey'] ) {
			return new \WP_Error(
				'graphql-headless-login-invalid-header-token',
				__( 'Invalid site token.', 'wp-graphql-headless-login' )
			);
		}

		return $args;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param array $user_data The user data.
	 *
	 * @return \WP_User|false
	 */
	public function get_user_from_data( $user_data ) {
		if ( empty( $user_data['subject_identity'] ) ) {
			return false;
		}

		$meta_key = $this->options['loginOptions']['metaKey'] ?? 'user_email';

		return User::get_user_by( $meta_key, $user_data['subject_identity'] );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return array{subject_identity: ?string}
	 *
	 * @throws UserError
	 */
	protected function prepare_mutation_input( array $input ) : array {
		if ( ! isset( $input['identity'] ) ) {
			throw new UserError(
				__( 'The SITE_TOKEN provider requires the use of the `identity` input arg.', 'wp-graphql-headless-login' )
			);
		}

		$args = [
			'subject_identity' => ! empty( $input['identity'] ) ? sanitize_text_field( $input['identity'] ) : null,
		];

		return $args;
	}

	/**
	 * {@inheritDoc}
	 */
	protected static function client_options_schema() : array {
		return [
			'headerKey' => [
				'type'        => 'string',
				'label'       => __( 'Header Key', 'wp-graphql-headless-login' ),
				'description' => __( 'The custom header that will be used to store the site access token.', 'wp-graphql-headless-login' ),
				'help'        => __( 'The custom header that will be used to store the site access token. The header should only be set on a SERVER-SIDE request. E.g. `X-My-Site-Token`', 'wp-graphql-headless-login' ),
				'order'       => 1,
			],
			'secretKey' => [
				'type'        => 'string',
				'label'       => __( 'Site Secret', 'wp-graphql-headless-login' ),
				'description' => __( 'The secret used to authenticate the site token.', 'wp-graphql-headless-login' ),
				'help'        => __( 'The secret used to authenticate the site token. This should be the same as the value you set on your custom Header key.  The secret should only be set on a SERVER-SIDE request.', 'wp-graphql-headless-login' ),
				'order'       => 2,
			],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	protected static function login_options_schema(): array {
		return [
			'metaKey' => [
				'type'        => 'string',
				'description' => __( 'The User meta key to check for the identity', 'wp-graphql-headless-login' ),
				'default'     => 'email',
				'help'        => __( 'The WP_User key to check for the identity. Accepts `id`, `slug`, `email`, `login`, or a custom meta field.', 'wp-graphql-headless-login' ),
				'order'       => 1,
			],
		];
	}
}
