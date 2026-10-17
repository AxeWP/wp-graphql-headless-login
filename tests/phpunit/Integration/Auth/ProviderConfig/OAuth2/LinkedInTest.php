<?php
/**
 * Tests the login and linkUserIdentity mutations for the LinkedIn provider.
 *
 * @package WPGraphQL\Login\Tests\Integration\Auth\ProviderConfig\OAuth2
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Tests\Integration\Auth\ProviderConfig\OAuth2;

use PHPUnit\Framework\Attributes\CoversClass;
use WPGraphQL\Login\Auth\Auth;
use WPGraphQL\Login\Auth\Client;
use WPGraphQL\Login\Auth\ProviderConfig\OAuth2\LinkedIn;
use WPGraphQL\Login\Auth\ProviderConfig\OAuth2\OAuth2Config;
use WPGraphQL\Login\Auth\User;
use WPGraphQL\Login\GraphQL\Type\Mutation\LinkUserIdentity;
use WPGraphQL\Login\GraphQL\Type\Mutation\Login;
use WPGraphQL\Login\Tests\Fixtures\FooLinkedInProviderConfig;

/**
 * Tests the LinkedIn provider mutations.
 */
#[CoversClass( LinkedIn::class )]
#[CoversClass( OAuth2Config::class )]
#[CoversClass( Login::class )]
#[CoversClass( LinkUserIdentity::class )]
#[CoversClass( Auth::class )]
#[CoversClass( Client::class )]
#[CoversClass( User::class )]
class LinkedInTest extends OAuth2ConfigTestCase {
	/**
	 * {@inheritDoc}
	 */
	protected function get_provider_slug(): string {
		return 'linkedin';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_provider_enum(): string {
		return 'LINKEDIN';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_client_options(): array {
		return [
			'scope' => [
				'r_liteprofile',
				'r_emailaddress',
			],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	protected function create_provider_config(): OAuth2Config {
		return new FooLinkedInProviderConfig();
	}

	/**
	 * {@inheritDoc}
	 *
	 * The username is derived from the email address.
	 */
	protected function get_provider_user_fields(): array {
		return [
			'email'     => 'mock_email@email.com',
			'firstName' => 'mock_first_name',
			'lastName'  => 'mock_last_name',
			'username'  => 'mock_email',
		];
	}
}
