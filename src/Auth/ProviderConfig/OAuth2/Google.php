<?php
/**
 * The Google Provider class.
 *
 * @package WPGraphQL\Login\Auth\ProviderConfig\OAuth2
 * @since 0.0.1
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Auth\ProviderConfig\OAuth2;

use WPGraphQL\Login\Type\Enum\GoogleProviderPromptTypeEnum;
use WPGraphQL\Login\Vendor\League\OAuth2\Client\Provider\Google as GoogleProvider;

/**
 * Class - Google
 */
class Google extends OAuth2Config {
	/**
	 * The Constructor.
	 */
	public function __construct() {
		parent::__construct( GoogleProvider::class );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_name(): string {
		return __( 'Google', 'wp-graphql-headless-login' );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_slug(): string {
		return 'google';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_options( array $settings ): array {
		return [
			'clientId'     => $settings['clientId'] ?? null,
			'clientSecret' => $settings['clientSecret'] ?? null,
			'redirectUri'  => $settings['redirectUri'] ?? null,
			'hostedDomain' => ! empty( $settings['hostedDomain'] ) ? $settings['hostedDomain'] : null,
			'prompt'       => ! empty( $settings['promptType'] ) ? $settings['promptType'] : 'consent',
			'scope'        => ! empty( $settings['scope'] ) ? $settings['scope'] : [],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	protected static function client_options_schema(): array {
		return [
			'hostedDomain' => [
				'type'        => 'string',
				'description' => __( 'Hosted Domain', 'wp-graphql-headless-login' ),
				'help'        => __( 'Streamline the login process for accounts owned by a Google Cloud organization. To optimize for Google Cloud organization accounts generally instead of just one organization domain, set a value of an asterisk `*`.', 'wp-graphql-headless-login' ),
				'order'       => 10,
			],
			'promptType'   => [
				'type'        => 'string',
				'description' => __( 'Prompt Type', 'wp-graphql-headless-login' ),
				'help'        => __( 'The type of prompt displayed to the user when authenticating.', 'wp-graphql-headless-login' ),
				'enum'        => [
					'none',
					'consent',
					'select_account',
				],
				'order'       => 11,
			],
			'scope'        => [
				'type'        => 'array',
				'description' => __( 'Scope', 'wp-graphql-headless-login' ),
				'help'        => __( 'The scope to request from the Google OAuth2 API. See https://developers.google.com/identity/protocols/oauth2/scope for a list of available scopes.', 'wp-graphql-headless-login' ),
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
			'hostedDomain' => [
				'type'        => 'String',
				'description' => __( 'The Google Cloud organization the OAuth prompt is optimized for. If `*` the prompt is optimized for general use with any Google Cloud organization.', 'wp-graphql-headless-login' ),
			],
			'promptType'   => [
				'type'        => GoogleProviderPromptTypeEnum::get_type_name(),
				'description' => __( 'The prompt used for authentication and consent. Defaults to `consent`.', 'wp-graphql-headless-login' ),
			],
			'scope'        => [
				'type'        => [ 'list_of' => 'String' ],
				'description' => __( 'The scope to request from the Google Graph API. See https://developers.facebook.com/docs/graph-api/reference/user for a list of available scopes.', 'wp-graphql-headless-login' ),
			],
		];
	}

	/**
	 * Maps the provider's user data to WP_User arguments.
	 *
	 * @param array<string,mixed> $owner_details The Resource Owner details returned from the OAuth2 provider.
	 *
	 * @return array<string,mixed> The mapped user data.
	 */
	public function get_user_data( array $owner_details ): array {
		$email    = $owner_details['email'];
		$username = strstr( $email, '@', true );

		$first_name = $owner_details['given_name'] ?? null;
		$last_name  = $owner_details['family_name'] ?? null;

		return [
			'user_login'       => $username,
			'user_email'       => $email,
			'first_name'       => $first_name,
			'last_name'        => $last_name,
			'subject_identity' => (string) $owner_details['sub'],
		];
	}
}
