<?php
/**
 * A mock FooLinkedInProvider for testing the provider mutations.
 *
 * @package Tests\WPGraphQL\Login\Fixtures
 */

declare( strict_types = 1 );

namespace Tests\WPGraphQL\Login\Fixtures;

use WPGraphQL\Login\Vendor\League\OAuth2\Client\Provider\LinkedIn;

/**
 * A LinkedIn provider with mocked resource owner details.
 */
class FooLinkedInProvider extends LinkedIn {
	/**
	 * {@inheritDoc}
	 */
	protected function fetchResourceOwnerDetails( $token ) {
		return json_decode( '{"id": 12345, "firstName": "mock_first_name", "lastName": "mock_last_name", "vanityName": "mock_username"}', true );
	}

	/**
	 * {@inheritDoc}
	 */
	public function getResourceOwnerEmail( $token ) {
		return 'mock_email@email.com';
	}
}
