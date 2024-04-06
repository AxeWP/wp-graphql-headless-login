<?php
/**
 * The GitHub Provider class.
 *
 * @package WPGraphQL\Login\Auth\ProviderConfig\OAuth2
 * @since 0.0.1
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Auth\ProviderConfig\OAuth2;

use WPGraphQL\Login\Vendor\League\OAuth2\Client\Provider\Github as GithubProvider;

/**
 * Class - GitHub
 */
class GitHub extends OAuth2Config {
	/**
	 * The Constructor.
	 */
	public function __construct() {
		parent::__construct( GithubProvider::class );
	}

		/**
		 * {@inheritDoc}
		 */
	public static function get_name(): string {
		return __( 'GitHub', 'wp-graphql-headless-login' );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_slug(): string {
		return 'github';
	}

	/**
	 * {@inheritDoc}
	 */
	protected static function client_options_schema(): array {
		return [
			'scope' => [
				'type'        => 'array',
				'description' => __( 'Scope', 'wp-graphql-headless-login' ),
				'help'        => __( 'The scope to request from the provider. See https://docs.github.com/en/developers/apps/building-headless-login-apps/scope-for-headless-login-apps for a list of available scope.', 'wp-graphql-headless-login' ),
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
				'description' => __( 'The scope to request from the provider. See https://docs.github.com/en/developers/apps/building-headless-login-apps/scope-for-headless-login-apps for a list of available scope.', 'wp-graphql-headless-login' ),
			],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_user_data( array $owner_details ): array {
		$name_parts = explode( ' ', $owner_details['name'] ?? '' );

		// Get a string from all parts but last.
		$first_name = implode( ' ', array_slice( $name_parts, 0, -1 ) ) ?: null;
		$last_name  = count( $name_parts ) > 1 ? end( $name_parts ) : null;

		return [
			'user_login'       => $owner_details['login'] ?? null,
			'user_email'       => $owner_details['email'] ?? null,
			'first_name'       => $first_name,
			'last_name'        => $last_name,
			'description'      => $owner_details['bio'] ?? null,
			'user_url'         => $owner_details['blog'] ?? null,
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
