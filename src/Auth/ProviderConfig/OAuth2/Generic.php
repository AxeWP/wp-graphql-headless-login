<?php
/**
 * The Generic Provider class.
 *
 * @package WPGraphQL\Login\Auth\ProviderConfig\OAuth2
 * @since 0.0.1
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Auth\ProviderConfig\OAuth2;

use WPGraphQL\Login\Vendor\League\OAuth2\Client\Provider\GenericProvider;

/**
 * Class - Generic
 */
class Generic extends OAuth2Config {
	/**
	 * The Constructor.
	 */
	public function __construct() {
		parent::__construct( GenericProvider::class );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_name(): string {
		return __( 'OAuth2 (Generic)', 'wp-graphql-headless-login' );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_slug(): string {
		return 'oauth2-generic';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_options( array $settings ): array {
		return [
			'clientId'                => $settings['clientId'] ?? null,
			'clientSecret'            => $settings['clientSecret'] ?? null,
			'redirectUri'             => $settings['redirectUri'] ?? null,
			'urlAuthorize'            => ! empty( $settings['urlAuthorize'] ) ? $settings['urlAuthorize'] : null,
			'urlAccessToken'          => ! empty( $settings['urlAccessToken'] ) ? $settings['urlAccessToken'] : null,
			'urlResourceOwnerDetails' => ! empty( $settings['urlResourceOwnerDetails'] ) ? $settings['urlResourceOwnerDetails'] : null,
			'scope'                   => ! empty( $settings['scope'] ) ? $settings['scope'] : [],
			'scopeSeparator'          => $settings['scopeSeparator'] ?? ',',
		];
	}

	/**
	 * {@inheritDoc}
	 */
	protected static function client_options_schema(): array {
		return [
			'urlAuthorize'            => [
				'type'        => 'string',
				'description' => __( 'Authorization URL', 'wp-graphql-headless-login' ),
				'help'        => __( 'The URL to redirect the user to in order to authorize the client.', 'wp-graphql-headless-login' ),
				'order'       => 10,
			],
			'urlAccessToken'          => [
				'type'        => 'string',
				'description' => __( 'Access token URL', 'wp-graphql-headless-login' ),
				'help'        => __( 'The URL to request an access token.', 'wp-graphql-headless-login' ),
				'order'       => 11,
			],
			'urlResourceOwnerDetails' => [
				'type'        => 'string',
				'description' => __( 'Resource Owner URL', 'wp-graphql-headless-login' ),
				'help'        => __( 'The URL to request the resource owner details.', 'wp-graphql-headless-login' ),
				'order'       => 12,
			],
			'scope'                   => [
				'type'        => 'array',
				'description' => __( 'Scope', 'wp-graphql-headless-login' ),
				'help'        => __( 'The scope to request from the Generic OAuth2 API.', 'wp-graphql-headless-login' ),
				'order'       => 12,
				'advanced'    => true,
				'items'       => [
					'type' => 'string',
				],
			],
			'scopeSeparator'          => [
				'type'        => 'string',
				'description' => __( 'Scope Separator', 'wp-graphql-headless-login' ),
				'help'        => __( 'The scope separator to use when building the authorization URL. Defaults to `,`.', 'wp-graphql-headless-login' ),
			],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	protected static function client_options_fields(): array {
		return [
			'authorizationUrl' => [
				'type'        => 'String',
				'description' => __( 'The URL to redirect the user to in order to authorize the client.', 'wp-graphql-headless-login' ),
				'resolve'     => static fn ( $source ): string => $source['urlAuthorize'],
			],
			'accessTokenUrl'   => [
				'type'        => 'String',
				'description' => __( 'The URL to request an access token.', 'wp-graphql-headless-login' ),
				'resolve'     => static fn ( $source ): string => $source['urlAccessToken'],
			],
			'resourceOwnerUrl' => [
				'type'        => 'String',
				'description' => __( 'The URL to request the resource owner details.', 'wp-graphql-headless-login' ),
				'resolve'     => static fn ( $source ): string => $source['urlResourceOwnerDetails'],
			],
			'scope'            => [
				'type'        => [ 'list_of' => 'String' ],
				'description' => __( 'The fields to request from the Generic Graph API. See https://developers.facebook.com/docs/graph-api/reference/user for a list of available fields.', 'wp-graphql-headless-login' ),
			],
			'scopeSeparator'   => [
				'type'        => 'String',
				'description' => __( 'The scope separator to use when building the authorization URL. Defaults to `,`.', 'wp-graphql-headless-login' ),
			],
		];
	}

	/**
	 * Maps the provider's user data to WP_User arguments.
	 *
	 * @param array<string,mixed> $owner_details The Resource Owner details returned from the OAuth2 provider.
	 *
	 * @return array<string,mixed> The WP_User arguments.
	 */
	public function get_user_data( array $owner_details ): array {
		$email    = $owner_details['email'] ?? null;
		$username = $owner_details['username'] ?? ( strstr( (string) $email, '@', true ) ?: null );

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
