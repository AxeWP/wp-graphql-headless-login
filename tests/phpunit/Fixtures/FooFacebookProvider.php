<?php
/**
 * A mock FooFacebookProvider for testing the provider mutations.
 *
 * @package Tests\WPGraphQL\Login\Fixtures
 */

declare( strict_types = 1 );

namespace Tests\WPGraphQL\Login\Fixtures;

use WPGraphQL\Login\Vendor\League\OAuth2\Client\Provider\Facebook;

/**
 * A Facebook provider with mocked resource owner details.
 */
class FooFacebookProvider extends Facebook {
	/**
	 * {@inheritDoc}
	 */
	protected function fetchResourceOwnerDetails( $token ) {
		return json_decode( '{"id": 12345, "name": "mock_name", "username": "mock_username", "first_name": "mock_first_name", "last_name": "mock_last_name", "email": "mock_email@email.com", "Location": "mock_home", "link": "mock_facebook_url"}', true );
	}
}
