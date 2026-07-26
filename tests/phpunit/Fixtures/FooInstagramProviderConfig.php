<?php
/**
 * A mock FooInstagramProviderConfig for testing the provider mutations.
 *
 * @package Tests\WPGraphQL\Login\Fixtures
 */

declare( strict_types = 1 );

namespace Tests\WPGraphQL\Login\Fixtures;

use Mockery as m;
use WPGraphQL\Login\Auth\ProviderConfig\OAuth2\Instagram as OAuth2Instagram;
use WPGraphQL\Login\Auth\ProviderConfig\OAuth2\OAuth2Config;

/**
 * An Instagram provider config wired to the mocked provider and HTTP client.
 */
class FooInstagramProviderConfig extends OAuth2Instagram {
	/**
	 * {@inheritDoc}
	 */
	public function __construct() {
		OAuth2Config::__construct( FooInstagramProvider::class );

		// Mock and set the http client on the provider.
		$response = m::mock( 'WPGraphQL\Login\Vendor\Psr\Http\Message\ResponseInterface' );
		$response->shouldReceive( 'getBody' )
			->andReturn(
				\WPGraphQL\Login\Vendor\GuzzleHttp\Psr7\Utils::streamFor( '{"access_token":"mock_access_token", "scope":"repo,gist", "token_type":"bearer"}' )
			);
		$response->shouldReceive( 'getHeader' )
			->andReturn( [ 'Content-Type' => 'application/json' ] );
		$response->shouldReceive( 'getStatusCode' )
			->andReturn( 200 );

		$http_client = m::mock( 'WPGraphQL\Login\Vendor\GuzzleHttp\ClientInterface' );
		$http_client->shouldReceive( 'send' )->times( 1 )->andReturn( $response );
		$this->provider->setHttpClient( $http_client );
	}
}
