<?php
/**
 * A mock FooInstagramProvider for testing the provider mutations.
 *
 * @package Tests\WPGraphQL\Login\Fixtures
 */

declare( strict_types = 1 );

namespace Tests\WPGraphQL\Login\Fixtures;

use WPGraphQL\Login\Vendor\League\OAuth2\Client\Provider\Instagram;

/**
 * An Instagram provider with mocked resource owner details.
 */
class FooInstagramProvider extends Instagram {
	/**
	 * {@inheritDoc}
	 */
	protected function fetchResourceOwnerDetails( $token ) {
		return json_decode( '{"id": 12345, "username": "mock_username"}', true );
	}
}
