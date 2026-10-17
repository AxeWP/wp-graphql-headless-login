<?php
/**
 * Tests the login and linkUserIdentity mutations for the Instagram provider.
 *
 * @package WPGraphQL\Login\Tests\Integration\Auth\ProviderConfig\OAuth2
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Tests\Integration\Auth\ProviderConfig\OAuth2;

use PHPUnit\Framework\Attributes\CoversClass;
use WPGraphQL\Login\Auth\Auth;
use WPGraphQL\Login\Auth\Client;
use WPGraphQL\Login\Auth\ProviderConfig\OAuth2\Instagram;
use WPGraphQL\Login\Auth\ProviderConfig\OAuth2\OAuth2Config;
use WPGraphQL\Login\Auth\User;
use WPGraphQL\Login\GraphQL\Type\Mutation\LinkUserIdentity;
use WPGraphQL\Login\GraphQL\Type\Mutation\Login;
use WPGraphQL\Login\Tests\Fixtures\FooInstagramProviderConfig;

/**
 * Tests the Instagram provider mutations.
 */
#[CoversClass( Instagram::class )]
#[CoversClass( OAuth2Config::class )]
#[CoversClass( Login::class )]
#[CoversClass( LinkUserIdentity::class )]
#[CoversClass( Auth::class )]
#[CoversClass( Client::class )]
#[CoversClass( User::class )]
class InstagramTest extends OAuth2ConfigTestCase {
	/**
	 * {@inheritDoc}
	 */
	protected function get_provider_slug(): string {
		return 'instagram';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_provider_enum(): string {
		return 'INSTAGRAM';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_client_options(): array {
		return [
			'scope' => [
				'user_profile',
				'user_media',
			],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	protected function create_provider_config(): OAuth2Config {
		return new FooInstagramProviderConfig();
	}

	/**
	 * {@inheritDoc}
	 *
	 * Instagram only returns a username.
	 */
	protected function get_provider_user_fields(): array {
		return [
			'username' => 'mock_username',
		];
	}

	/**
	 * Skipped: `linkExistingUsers` matches users by email address, which Instagram doesn't return.
	 */
	public function test_login_with_link_existing_users(): void {
		$this->markTestSkipped( 'Instagram does not provide an email address.' );
	}
}
