<?php
/**
 * A Google provider with mocked resource owner details.
 *
 * @package WPGraphQL\Login\Tests\Fixtures
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Tests\Fixtures;

use WPGraphQL\Login\Vendor\League\OAuth2\Client\Provider\Google;
use WPGraphQL\Login\Vendor\League\OAuth2\Client\Token\AccessToken;

/**
 * A Google provider that returns canned resource owner details.
 */
class FooGoogleProvider extends Google {
	/**
	 * {@inheritDoc}
	 *
	 * @return array<string,mixed>
	 */
	protected function fetchResourceOwnerDetails( AccessToken $token ): array {
		return [
			'sub'         => 12345,
			'name'        => 'mock_name',
			'given_name'  => 'mock_first_name',
			'family_name' => 'mock_last_name',
			'email'       => 'mock_email@mockdomain.com',
			'picture'     => 'mock_image_url',
			'hd'          => 'mockdomain.com',
		];
	}
}
