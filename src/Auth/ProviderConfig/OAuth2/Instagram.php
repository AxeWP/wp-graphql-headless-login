<?php
/**
 * The Instagram Provider class.
 *
 * @package WPGraphQL\Login\Auth\ProviderConfig\OAuth2
 * @since 0.0.3
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Auth\ProviderConfig\OAuth2;

use WPGraphQL\Login\Vendor\League\OAuth2\Client\Provider\Instagram as InstagramProvider;

/**
 * Class - Instagram
 */
class Instagram extends OAuth2Config {
	/**
	 * The Constructor.
	 */
	public function __construct() {
		parent::__construct( InstagramProvider::class );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_name(): string {
		return __( 'Instagram', 'wp-graphql-headless-login' );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_slug(): string {
		return 'instagram';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_options( array $settings ): array {
		return [
			'clientId'     => $settings['clientId'] ?? null,
			'clientSecret' => $settings['clientSecret'] ?? null,
			'redirectUri'  => $settings['redirectUri'] ?? null,
			'scope'        => ! empty( $settings['scope'] ) ? $settings['scope'] : [],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	protected static function client_options_schema(): array {
		return [
			'scope' => [
				'type'        => 'array',
				'description' => __( 'Scope', 'wp-graphql-headless-login' ),
				'help'        => __( 'The Scope to request from the Instagram OAuth2 API. See https://developers.facebook.com/docs/instagram-basic-display-api/overview#permissions for a list of available scopes.', 'wp-graphql-headless-login' ),
				'order'       => 12,
				'advanced'    => true,
				'items'       => [
					'type' => 'string',
				],
			],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	protected static function client_options_fields(): array {
		return [
			'scope' => [
				'type'        => [ 'list_of' => 'String' ],
				'description' => __( 'The scope to request from the Instagram Graph API. See https://developers.facebook.com/docs/instagram-basic-display-api/overview#permissions for a list of available scopes.', 'wp-graphql-headless-login' ),
			],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	protected static function login_options_fields(): array {
		// Instagram doesnt give us enough information to link an existing user.
		return [];
	}

	/**
	 * {@inheritDoc}
	 */
	protected static function login_options_schema(): array {
		// Instagram doesnt give us enough information to link an existing user.
		return [];
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_user_data( array $owner_details ): array {
		return [
			'user_login'       => $owner_details['username'],
			'user_email'       => null,
			'first_name'       => null,
			'last_name'        => null,
			'subject_identity' => (string) $owner_details['id'],
		];
	}
}
