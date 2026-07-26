<?php
/**
 * A mock FooGoogleProvider for testing the provider mutations.
 *
 * @package Tests\WPGraphQL\Login\Fixtures
 */

declare( strict_types = 1 );

namespace Tests\WPGraphQL\Login\Fixtures;

use WPGraphQL\Login\Vendor\League\OAuth2\Client\Provider\Google;

/**
 * A Google provider with mocked resource owner details.
 */
class FooGoogleProvider extends Google {
	/**
	 * {@inheritDoc}
	 */
	protected function fetchResourceOwnerDetails( $token ) {
		return json_decode( '{"sub": 12345, "name": "mock_name", "given_name": "mock_first_name", "family_name": "mock_last_name", "email": "mock_email@mockdomain.com", "email_verified": true, "picture": "mock_image_url", "hd":"mockdomain.com"}', true );
	}
}
