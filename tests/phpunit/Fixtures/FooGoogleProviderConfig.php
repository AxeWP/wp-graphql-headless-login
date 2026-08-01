<?php
/**
 * A mock FooGoogleProviderConfig for testing the provider mutations.
 *
 * @package Tests\WPGraphQL\Login\Fixtures
 */

declare( strict_types = 1 );

namespace Tests\WPGraphQL\Login\Fixtures;

use Mockery as m;
use WPGraphQL\Login\Auth\ProviderConfig\OAuth2\Google as OAuth2Google;
use WPGraphQL\Login\Auth\ProviderConfig\OAuth2\OAuth2Config;

/**
 * A Google provider config wired to the mocked provider and HTTP client.
 */
class FooGoogleProviderConfig extends OAuth2Google {
	/**
	 * {@inheritDoc}
	 */
	public function __construct() {
		OAuth2Config::__construct( FooGoogleProvider::class );

		// Mock and set the http client on the provider.
		$response = m::mock( 'WPGraphQL\Login\Vendor\Psr\Http\Message\ResponseInterface' );
		$response->shouldReceive( 'getHeader' )
			->times( 1 )
			->andReturn( [ 'Content-Type' => 'application/json' ] );
		$response->shouldReceive( 'getBody' )
			->times( 1 )
			->andReturn(
				\WPGraphQL\Login\Vendor\GuzzleHttp\Psr7\Utils::streamFor( '{"access_token":"mock_access_token","token_type":"bearer","expires_in":3600}' )
			);

		$http_client = m::mock( 'WPGraphQL\Login\Vendor\GuzzleHttp\ClientInterface' );
		$http_client->shouldReceive( 'send' )->times( 1 )->andReturn( $response );
		$this->provider->setHttpClient( $http_client );
	}
}
