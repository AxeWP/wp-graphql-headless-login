<?php
/**
 * Tests the login and linkUserIdentity mutations for the Facebook provider.
 *
 * @package WPGraphQL\Login\Tests\Integration\Auth\ProviderConfig\OAuth2
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Tests\Integration\Auth\ProviderConfig\OAuth2;

use PHPUnit\Framework\Attributes\CoversClass;
use WPGraphQL\Login\Auth\Auth;
use WPGraphQL\Login\Auth\Client;
use WPGraphQL\Login\Auth\ProviderConfig\OAuth2\Facebook;
use WPGraphQL\Login\Auth\ProviderConfig\OAuth2\OAuth2Config;
use WPGraphQL\Login\Auth\User;
use WPGraphQL\Login\GraphQL\Type\Mutation\LinkUserIdentity;
use WPGraphQL\Login\GraphQL\Type\Mutation\Login;
use WPGraphQL\Login\Tests\Fixtures\FooFacebookProviderConfig;

/**
 * Tests the Facebook provider mutations.
 */
#[CoversClass( Facebook::class )]
#[CoversClass( OAuth2Config::class )]
#[CoversClass( Login::class )]
#[CoversClass( LinkUserIdentity::class )]
#[CoversClass( Auth::class )]
#[CoversClass( Client::class )]
#[CoversClass( User::class )]
class FacebookTest extends OAuth2ConfigTestCase {
	/**
	 * {@inheritDoc}
	 */
	protected function get_provider_slug(): string {
		return 'facebook';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_provider_enum(): string {
		return 'FACEBOOK';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_client_options(): array {
		return [
			'graphAPIVersion' => 'v16.0',
			'enableBetaTier'  => false,
			'scope'           => [
				'email',
				'public_profile',
			],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	protected function create_provider_config(): OAuth2Config {
		return new FooFacebookProviderConfig();
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_provider_user_fields(): array {
		return [
			'email'     => 'mock_email@email.com',
			'firstName' => 'mock_first_name',
			'lastName'  => 'mock_last_name',
			'username'  => 'mock_username',
		];
	}
}
