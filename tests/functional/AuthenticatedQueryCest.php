<?php

use WPGraphQL\Login\Admin\Settings\AccessControlSettings;

class AuthenticatedQueryCest {
	public function testQueryWithInvalidHeaders( FunctionalTester $I ) {
		$I->wantTo( 'Query with invalid authentication headers.' );

		$user_id = $I->haveUserInDatabase( 'testuser', 'administrator', [ 'user_pass' => 'testpass' ] );

		$I->haveGraphQLDebug();

		$auth_token = 'invalid-auth-token';

		$post_id = $I->havePostInDatabase(
			[
				'post_title'   => 'Test Post',
				'post_type'    => 'post',
				'post_status'  => 'publish',
				'post_content' => 'Post Content',
			]
		);

		$query =
			'query {
				posts {
					edges {
						node {
							id
							title
							link
							date
						}
					}
				}
				viewer {
					databaseId
					username
					auth {
						authToken
						authTokenExpiration
						refreshToken
						refreshTokenExpiration
						isUserSecretRevoked
						userSecret
					}
				}
			}
		';

		// Set the content-type so we get a proper response from the API.
		$I->haveHttpHeader( 'Content-Type', 'application/json' );
		$I->setHeader( 'Authorization', 'Bearer ' . $auth_token );

		$I->sendPost(
			// Use site url.
			'/graphql',
			json_encode(
				[
					'query' => $query,
				],
			)
		);

		// Check response.
		$I->seeResponseCodeIs( 403 );
		$I->dontSeeHttpHeader( 'X-WPGraphQL-Login-Token' );
		$I->dontSeeHttpHeader( 'X-WPGraphQL-Login-Refresh-Token' );
		$I->seeResponseIsJson();

		$response = $I->grabResponse();
		$response = json_decode( $response, true );

		// The response has authentication errors.
		$I->assertEquals( 'invalid-secret-key | Wrong number of segments', $response['extensions']['debug'][0]['message'] );

		// The response also has valid data.
		$I->assertArrayHasKey( 'data', $response );

		// The viewer is not authenticated.
		$I->assertArrayHasKey( 'viewer', $response['data'] );
		$I->assertNull( $response['data']['viewer'] );

		// The posts are still returned.
		$I->assertArrayHasKey( 'posts', $response['data'] );
		$I->assertNotEmpty( $response['data']['posts']['edges'][0]['node']['id'] );
		$I->assertNotEmpty( $response['data']['posts']['edges'][0]['node']['title'] );
		$I->assertNotEmpty( $response['data']['posts']['edges'][0]['node']['link'] );
		$I->assertNotEmpty( $response['data']['posts']['edges'][0]['node']['date'] );
	}

	public function testQueryWithNoHeaders( FunctionalTester $I ) {
		$I->wantTo( 'Get public data without authentication headers' );

		$post_id = $I->havePostInDatabase(
			[
				'post_title'   => 'Test Post',
				'post_type'    => 'post',
				'post_status'  => 'publish',
				'post_content' => 'Post Content',
			]
		);

		$I->haveGraphQLDebug();

		$query = '
			query {
				posts {
					edges {
						node {
							id
							title
							link
							date
						}
					}
				}
			}
		';

		$response = $I->sendGraphQLRequest( $query );

		$I->dontSeeHttpHeader( 'X-WPGraphQL-Login-Token' );
		$I->dontSeeHttpHeader( 'X-WPGraphQL-Login-Refresh-Token' );

		// The query is valid and has no errors.
		$I->assertArrayNotHasKey( 'errors', $response );
		$I->assertEmpty( $response['extensions']['debug'] );

		// The response is properly returning data as expected.
		$I->assertArrayHasKey( 'data', $response );
		$I->assertNotEmpty( $response['data']['posts']['edges'][0]['node']['id'] );
		$I->assertNotEmpty( $response['data']['posts']['edges'][0]['node']['title'] );
		$I->assertNotEmpty( $response['data']['posts']['edges'][0]['node']['link'] );
		$I->assertNotEmpty( $response['data']['posts']['edges'][0]['node']['date'] );
	}

	public function testQueryWithHeaders( FunctionalTester $I ) {
		$I->wantTo( 'Query with Access Control headers configured' );

		$user_id = $I->haveUserInDatabase( 'testuser', 'administrator', [ 'user_pass' => 'testpass' ] );
		$I->haveGraphQLDebug();

		$I->haveOptionInDatabase(
			AccessControlSettings::$settings_prefix . 'access_control',
			[
				'shouldBlockUnauthorizedDomains' => true,
				'hasSiteAddressInOrigin'         => true,
				'additionalAuthorizedDomains'    => [
					'example.com',
				],
				'customHeaders'                  => [
					'X-Custom-Header',
				],
			]
		);
		$I->reset_utils_properties();

		$expected_tokens = $I->generateUserTokens( $user_id );

		$query = 'query {
			viewer { 
				databaseId
				username
				auth {
					authToken
					authTokenExpiration
					refreshToken
					refreshTokenExpiration
					isUserSecretRevoked
					userSecret
				}
			}
		}';

		// Send Request.

		$I->haveHttpHeader( 'Content-Type', 'application/json' );
		$I->haveHttpHeader( 'Authorization', 'Bearer ' . $expected_tokens['auth_token'] );

		$I->sendPost(
			'/graphql',
			json_encode(
				[
					'query' => $query,
				]
			)
		);

		$I->seeResponseCodeIs( 403 );

		$I->haveHttpHeader( 'Origin', 'https://example.com' );

		$I->reset_utils_properties();

		$response = $I->sendGraphQLRequest(
			$query,
			null,
			[
				'Authorization' => 'Bearer ' . $expected_tokens['auth_token'],
			]
		);

		$I->seeHttpHeader( 'X-WPGraphQL-Login-Token' );
		$I->seeHttpHeader( 'X-WPGraphQL-Login-Refresh-Token' );

		// The query is valid and has no errors.
		$I->assertArrayNotHasKey( 'errors', $response );
		$I->assertEmpty( $response['extensions']['debug'] );

		$I->assertArrayHasKey( 'data', $response );
		$I->assertEquals( $user_id, $response['data']['viewer']['databaseId'] );
		$I->assertEquals( 'testuser', $response['data']['viewer']['username'] );
		$I->assertNotEmpty( $response['data']['viewer']['auth']['authToken'] );
		$I->assertNotEmpty( $response['data']['viewer']['auth']['authTokenExpiration'] );
		$I->assertNotEmpty( $response['data']['viewer']['auth']['refreshToken'] );
		$I->assertNotEmpty( $response['data']['viewer']['auth']['refreshTokenExpiration'] );
		$I->assertFalse( $response['data']['viewer']['auth']['isUserSecretRevoked'] );
		$I->assertNotEmpty( $response['data']['viewer']['auth']['userSecret'] );
	}
}
