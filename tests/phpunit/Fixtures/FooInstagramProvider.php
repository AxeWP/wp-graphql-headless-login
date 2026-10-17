<?php
/**
 * An Instagram provider with mocked resource owner details.
 *
 * @package WPGraphQL\Login\Tests\Fixtures
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Tests\Fixtures;

use WPGraphQL\Login\Vendor\League\OAuth2\Client\Provider\Instagram;
use WPGraphQL\Login\Vendor\League\OAuth2\Client\Token\AccessToken;

/**
 * An Instagram provider that returns canned resource owner details.
 */
class FooInstagramProvider extends Instagram {
	/**
	 * {@inheritDoc}
	 *
	 * @return array<string,mixed>
	 */
	protected function fetchResourceOwnerDetails( AccessToken $token ): array {
		return [
			'id'       => 12345,
			'username' => 'mock_username',
		];
	}
}
