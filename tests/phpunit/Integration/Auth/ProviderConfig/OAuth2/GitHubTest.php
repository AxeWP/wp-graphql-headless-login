<?php
/**
 * Tests the login and linkUserIdentity mutations for the GitHub provider.
 *
 * @package WPGraphQL\Login\Tests\Integration\Auth\ProviderConfig\OAuth2
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Tests\Integration\Auth\ProviderConfig\OAuth2;

use PHPUnit\Framework\Attributes\CoversClass;
use WPGraphQL\Login\Auth\Auth;
use WPGraphQL\Login\Auth\Client;
use WPGraphQL\Login\Auth\ProviderConfig\OAuth2\GitHub;
use WPGraphQL\Login\Auth\ProviderConfig\OAuth2\OAuth2Config;
use WPGraphQL\Login\Auth\User;
use WPGraphQL\Login\GraphQL\Type\Mutation\LinkUserIdentity;
use WPGraphQL\Login\GraphQL\Type\Mutation\Login;
use WPGraphQL\Login\Tests\Fixtures\FooGithubProviderConfig;

/**
 * Tests the GitHub provider mutations.
 */
#[CoversClass( GitHub::class )]
#[CoversClass( OAuth2Config::class )]
#[CoversClass( Login::class )]
#[CoversClass( LinkUserIdentity::class )]
#[CoversClass( Auth::class )]
#[CoversClass( Client::class )]
#[CoversClass( User::class )]
class GitHubTest extends OAuth2ConfigTestCase {
	/**
	 * {@inheritDoc}
	 */
	protected function get_provider_slug(): string {
		return 'github';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_provider_enum(): string {
		return 'GITHUB';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_client_options(): array {
		return [
			'scope' => [
				'repo',
				'gist',
			],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	protected function create_provider_config(): OAuth2Config {
		return new FooGithubProviderConfig();
	}

	/**
	 * {@inheritDoc}
	 *
	 * The mocked single-word `name` yields no first or last name.
	 */
	protected function get_provider_user_fields(): array {
		return [
			'email'    => 'mock_email@email.com',
			'username' => 'mock_username',
		];
	}
}
