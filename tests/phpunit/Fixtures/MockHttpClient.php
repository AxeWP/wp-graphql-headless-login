<?php
/**
 * A mocked HTTP client for the OAuth2 provider fixtures.
 *
 * @package WPGraphQL\Login\Tests\Fixtures
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Tests\Fixtures;

use Mockery;
use WPGraphQL\Login\Vendor\GuzzleHttp\ClientInterface;
use WPGraphQL\Login\Vendor\GuzzleHttp\Psr7\Utils;
use WPGraphQL\Login\Vendor\Psr\Http\Message\ResponseInterface;

/**
 * Builds an HTTP client that answers the OAuth2 access token request.
 *
 * The provider fixtures mock the resource owner details themselves, so the token exchange is the only request sent.
 */
final class MockHttpClient {
	/**
	 * Creates an HTTP client that returns a bearer access token for a single request.
	 */
	public static function create(): ClientInterface {
		$response = Mockery::mock( ResponseInterface::class );
		$response->shouldReceive( 'getStatusCode' )->andReturn( 200 );
		$response->shouldReceive( 'getHeader' )->andReturn( [ 'Content-Type' => 'application/json' ] );
		$response->shouldReceive( 'getBody' )->andReturn(
			Utils::streamFor( '{"access_token":"mock_access_token","token_type":"bearer","expires_in":3600}' )
		);

		$http_client = Mockery::mock( ClientInterface::class );
		$http_client->shouldReceive( 'send' )->once()->andReturn( $response );

		return $http_client;
	}
}
