<?php
/**
 * A Facebook provider with mocked resource owner details.
 *
 * @package WPGraphQL\Login\Tests\Fixtures
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Tests\Fixtures;

use WPGraphQL\Login\Vendor\League\OAuth2\Client\Provider\Facebook;
use WPGraphQL\Login\Vendor\League\OAuth2\Client\Token\AccessToken;

/**
 * A Facebook provider that returns canned resource owner details.
 */
class FooFacebookProvider extends Facebook {
	/**
	 * {@inheritDoc}
	 *
	 * @return array<string,mixed>
	 */
	protected function fetchResourceOwnerDetails( AccessToken $token ): array {
		return [
			'id'         => 12345,
			'name'       => 'mock_name',
			'username'   => 'mock_username',
			'first_name' => 'mock_first_name',
			'last_name'  => 'mock_last_name',
			'email'      => 'mock_email@email.com',
			'Location'   => 'mock_home',
			'link'       => 'mock_facebook_url',
		];
	}
}
