<?php
/**
 * The LinkedIn Provider class.
 *
 * @package WPGraphQL\Login\Auth\ProviderConfig\OAuth2
 * @since 0.0.3
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Auth\ProviderConfig\OAuth2;

use WPGraphQL\Login\Vendor\League\OAuth2\Client\Provider\LinkedIn as LinkedInProvider;

/**
 * Class - LinkedIn
 */
class LinkedIn extends OAuth2Config {
	/**
	 * The Constructor.
	 */
	public function __construct() {
		parent::__construct( LinkedInProvider::class );
	}

		/**
		 * {@inheritDoc}
		 */
	public static function get_name(): string {
		return __( 'LinkedIn', 'wp-graphql-headless-login' );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_slug(): string {
		return 'linkedin';
	}

	/**
	 * {@inheritDoc}
	 */
	protected static function client_options_schema(): array {
		return [
			'scope' => [
				'type'        => 'array',
				'description' => __( 'Scope', 'wp-graphql-headless-login' ),
				'help'        => sprintf(
					// translators: %s is the URL to the LinkedIn API documentation.
					__( 'The scope to request from the provider. See %s for a list of available scopes.', 'wp-graphql-headless-login' ),
					'https://learn.microsoft.com/en-us/linkedin/shared/authentication/authentication?context=linkedin%2Fcontext#permission-types',
				),
				'order'       => 10,
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
				'description' => sprintf(
					// translators: %s is the URL to the LinkedIn API documentation.
					__( 'The scope to request from the provider. See %s for a list of available scopes.', 'wp-graphql-headless-login' ),
					'https://learn.microsoft.com/en-us/linkedin/shared/authentication/authentication?context=linkedin%2Fcontext#permission-types',
				),
			],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_user_data( array $owner_details ): array {
		$email    = $owner_details['email'];
		$username = strstr( $email, '@', true );

		$first_name = $owner_details['firstName'] ?? null;
		$last_name  = $owner_details['lastName'] ?? null;

		return [
			'user_login'       => $username,
			'user_email'       => $email,
			'first_name'       => $first_name,
			'last_name'        => $last_name,
			'subject_identity' => (string) $owner_details['id'],
		];
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
}
