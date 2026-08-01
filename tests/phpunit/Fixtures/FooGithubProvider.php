<?php
/**
 * A mock FooGithubProvider for testing the provider mutations.
 *
 * @package Tests\WPGraphQL\Login\Fixtures
 */

declare( strict_types = 1 );

namespace Tests\WPGraphQL\Login\Fixtures;

use WPGraphQL\Login\Vendor\League\OAuth2\Client\Provider\Github;

/**
 * A GitHub provider with mocked resource owner details.
 */
class FooGithubProvider extends Github {
	/**
	 * {@inheritDoc}
	 */
	protected function fetchResourceOwnerDetails( $token ) {
		return json_decode( '{"id": 12345, "name": "mock_name", "email": "mock_email@email.com", "login": "mock_username"}', true );
	}
}
