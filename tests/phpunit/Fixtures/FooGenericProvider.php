<?php
/**
 * A Generic OAuth2 provider with mocked resource owner details.
 *
 * @package WPGraphQL\Login\Tests\Fixtures
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Tests\Fixtures;

use WPGraphQL\Login\Vendor\League\OAuth2\Client\Provider\GenericProvider;
use WPGraphQL\Login\Vendor\League\OAuth2\Client\Token\AccessToken;

/**
 * A Generic OAuth2 provider that returns canned resource owner details.
 */
class FooGenericProvider extends GenericProvider {
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
		];
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param array<string,mixed> $options Ignored in favor of a fixed state.
	 */
	public function getAuthorizationUrl( array $options = [] ): string {
		return parent::getAuthorizationUrl(
			[
				'state' => 'someState',
			]
		);
	}
}
