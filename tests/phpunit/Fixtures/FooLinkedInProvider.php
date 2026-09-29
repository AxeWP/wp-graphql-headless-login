<?php
/**
 * A LinkedIn provider with mocked resource owner details.
 *
 * @package WPGraphQL\Login\Tests\Fixtures
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Tests\Fixtures;

use WPGraphQL\Login\Vendor\League\OAuth2\Client\Provider\LinkedIn;
use WPGraphQL\Login\Vendor\League\OAuth2\Client\Token\AccessToken;

/**
 * A LinkedIn provider that returns canned resource owner details.
 */
class FooLinkedInProvider extends LinkedIn {
	/**
	 * {@inheritDoc}
	 *
	 * @return array<string,mixed>
	 */
	protected function fetchResourceOwnerDetails( AccessToken $token ): array {
		return [
			'id'         => 12345,
			'firstName'  => 'mock_first_name',
			'lastName'   => 'mock_last_name',
			'vanityName' => 'mock_username',
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function getResourceOwnerEmail( AccessToken $token ): string {
		return 'mock_email@email.com';
	}
}
