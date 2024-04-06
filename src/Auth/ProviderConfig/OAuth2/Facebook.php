<?php
/**
 * The Facebook Provider class.
 *
 * @package WPGraphQL\Login\Auth\ProviderConfig\OAuth2
 * @since 0.0.1
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Auth\ProviderConfig\OAuth2;

use WPGraphQL\Login\Vendor\League\OAuth2\Client\Provider\Facebook as FacebookProvider;

/**
 * Class - Facebook
 */
class Facebook extends OAuth2Config {
	/**
	 * The Constructor.
	 */
	public function __construct() {
		parent::__construct( FacebookProvider::class );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_name(): string {
		return __( 'Facebook', 'wp-graphql-headless-login' );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_slug(): string {
		return 'facebook';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_options( array $settings ): array {
		return [
			'clientId'        => $settings['clientId'] ?? null,
			'clientSecret'    => $settings['clientSecret'] ?? null,
			'redirectUri'     => $settings['redirectUri'] ?? null,
			'graphApiVersion' => $settings['graphAPIVersion'] ?? 'v15.0',
			'scope'           => ! empty( $settings['scope'] ) ? $settings['scope'] : [],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	protected static function client_options_schema(): array {
		return [
			'graphAPIVersion' => [
				'type'        => 'string',
				'description' => __( 'Graph API Version', 'wp-graphql-headless-login' ),
				'help'        => __( 'The version of the Facebook Graph API to use. E.g. `v15.0`.', 'wp-graphql-headless-login' ),
				'pattern'     => 'v(\d+\.){1,}\d+',
				'order'       => 10,
			],
			'enableBetaTier'  => [
				'type'        => 'boolean',
				'description' => __( 'Enable Beta Tier', 'wp-graphql-headless-login' ),
				'advanced'    => true,
				'order'       => 11,
			],
			'scope'           => [
				'type'        => 'array',
				'description' => __( 'User Fields', 'wp-graphql-headless-login' ),
				'help'        => __( 'The fields to request from the Facebook Graph API. See https://developers.facebook.com/docs/graph-api/reference/user for a list of available fields.', 'wp-graphql-headless-login' ),
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
			'graphApiVersion' => [
				'type'        => 'String',
				'description' => __( 'The Facebook Graph API version.', 'wp-graphql-headless-login' ),
				'resolve'     => static function ( array $settings ): ?string {
					return $settings['graphAPIVersion'] ?? null;
				},
			],
			'enableBetaTier'  => [
				'type'        => 'Boolean',
				'description' => __( 'Enable the Facebook Beta Tier.', 'wp-graphql-headless-login' ),
				'resolve'     => static fn ( $value ): bool => $value['enableBetaTier'] ?? false,
			],
			'scope'           => [
				'type'        => [ 'list_of' => 'String' ],
				'description' => __( 'The fields to request from the Facebook Graph API. See https://developers.facebook.com/docs/graph-api/reference/user for a list of available fields.', 'wp-graphql-headless-login' ),
			],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_user_data( array $owner_details ): array {
		$email    = $owner_details['email'];
		$username = $owner_details['username'] ?? strstr( $email, '@', true );

		$first_name = $owner_details['first_name'] ?? null;
		$last_name  = $owner_details['last_name'] ?? null;

		return [
			'user_login'       => $username,
			'user_email'       => $email,
			'first_name'       => $first_name,
			'last_name'        => $last_name,
			'subject_identity' => (string) $owner_details['id'],
		];
	}
}
