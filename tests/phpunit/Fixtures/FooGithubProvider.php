<?php
/**
 * A GitHub provider with mocked resource owner details.
 *
 * @package WPGraphQL\Login\Tests\Fixtures
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Tests\Fixtures;

use WPGraphQL\Login\Vendor\League\OAuth2\Client\Provider\Github;
use WPGraphQL\Login\Vendor\League\OAuth2\Client\Token\AccessToken;

/**
 * A GitHub provider that returns canned resource owner details.
 */
class FooGithubProvider extends Github {
	/**
	 * {@inheritDoc}
	 *
	 * @return array<string,mixed>
	 */
	protected function fetchResourceOwnerDetails( AccessToken $token ): array {
		return [
			'id'    => 12345,
			'name'  => 'mock_name',
			'email' => 'mock_email@email.com',
			'login' => 'mock_username',
		];
	}
}
