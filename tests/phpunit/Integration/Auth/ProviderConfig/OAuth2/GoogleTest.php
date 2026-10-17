<?php
/**
 * Tests the login and linkUserIdentity mutations for the Google provider.
 *
 * @package WPGraphQL\Login\Tests\Integration\Auth\ProviderConfig\OAuth2
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Tests\Integration\Auth\ProviderConfig\OAuth2;

use PHPUnit\Framework\Attributes\CoversClass;
use WPGraphQL\Login\Auth\Auth;
use WPGraphQL\Login\Auth\Client;
use WPGraphQL\Login\Auth\ProviderConfig\OAuth2\Google;
use WPGraphQL\Login\Auth\ProviderConfig\OAuth2\OAuth2Config;
use WPGraphQL\Login\Auth\User;
use WPGraphQL\Login\GraphQL\Type\Mutation\LinkUserIdentity;
use WPGraphQL\Login\GraphQL\Type\Mutation\Login;
use WPGraphQL\Login\Tests\Fixtures\FooGoogleProviderConfig;

/**
 * Tests the Google provider mutations.
 */
#[CoversClass( Google::class )]
#[CoversClass( OAuth2Config::class )]
#[CoversClass( Login::class )]
#[CoversClass( LinkUserIdentity::class )]
#[CoversClass( Auth::class )]
#[CoversClass( Client::class )]
#[CoversClass( User::class )]
class GoogleTest extends OAuth2ConfigTestCase {
	/**
	 * {@inheritDoc}
	 */
	protected function get_provider_slug(): string {
		return 'google';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_provider_enum(): string {
		return 'GOOGLE';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_client_options(): array {
		return [
			'hostedDomain' => 'mockdomain.com',
			'scope'        => [
				'email',
				'public_profile',
			],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	protected function create_provider_config(): OAuth2Config {
		return new FooGoogleProviderConfig();
	}

	/**
	 * {@inheritDoc}
	 *
	 * The username is derived from the email address.
	 */
	protected function get_provider_user_fields(): array {
		return [
			'email'     => 'mock_email@mockdomain.com',
			'firstName' => 'mock_first_name',
			'lastName'  => 'mock_last_name',
			'username'  => 'mock_email',
		];
	}
}
