<?php
/**
 * Registers the LinkUserIdentity mutation
 *
 * @package WPGraphQL\Login\Mutation
 * @since 0.0.1
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Mutation;

use GraphQL\Type\Definition\ResolveInfo;
use WPGraphQL\AppContext;
use WPGraphQL\Login\Auth\Auth;
use WPGraphQL\Login\Type\Enum\ProviderEnum;
use WPGraphQL\Login\Type\Input\OAuthProviderResponseInput;
use WPGraphQL\Login\Vendor\AxeWP\GraphQL\Abstracts\MutationType;
use WPGraphQL\Model\User;

/**
 * Class - LinkUserIdentity
 */
class LinkUserIdentity extends MutationType {
	/**
	 * {@inheritDoc}
	 */
	public static function type_name(): string {
		return 'LinkUserIdentity';
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_input_fields(): array {
		return [
			'oauthResponse' => [
				'type'        => OAuthProviderResponseInput::get_type_name(),
				'description' => __( 'The parsed response from an OAuth2 Authentication Provider.', 'wp-graphql-headless-login' ),
			],
			'identity'      => [
				'type'        => 'String',
				'description' => __( 'The user identity to use when logging in. Required by the SiteToken provider.', 'wp-graphql-headless-login' ),
			],
			'provider'      => [
				'type'        => [ 'non_null' => ProviderEnum::get_type_name() ],
				'description' => __( 'The Headless Login provider to use when logging in.', 'wp-graphql-headless-login' ),
			],
			'userId'        => [
				'type'        => [ 'non_null' => 'ID' ],
				'description' => __( 'The user ID to link the identity to. Accepts either a global or database ID.', 'wp-graphql-headless-login' ),
			],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_output_fields(): array {
		return [
			'success' => [
				'type'        => 'Boolean',
				'description' => __( 'Whether the identity was successfully linked to the user.', 'wp-graphql-headless-login' ),
			],
			'user'    => [
				'type'        => 'User',
				'description' => __( 'The user that was logged in.', 'wp-graphql-headless-login' ),
				'resolve'     => static function ( $payload ): ?User {
					return empty( $payload['user'] ) ? null : new User( $payload['user'] );
				},
			],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public static function mutate_and_get_payload(): callable {
		return static function ( array $input, AppContext $context, ResolveInfo $info ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
			// Validate the response, login the user, and get an authToken and user in response.
			return Auth::link_user_identity( $input );
		};
	}
}
