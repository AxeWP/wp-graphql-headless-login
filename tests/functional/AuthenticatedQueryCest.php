<?php

use WPGraphQL\Login\Admin\Settings\PluginSettings;
use WPGraphQL\Login\Auth\TokenManager;

class AuthenticatedQueryCest {


	public function testQueryWithValidHeaders( FunctionalTester $I ) {
		$I->wantTo( 'Query with valid authentication headers' );

		$user_id = $I->haveUserInDatabase( 'testuser', 'administrator', [ 'user_pass' => 'testpass' ] );

		$I->haveGraphQLDebug();

		$expected_tokens = $this->generate_tokens( $user_id, $I );

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

		$response = $I->sendGraphQLRequest( $query, null, [
			'Authorization' => 'Bearer ' . $expected_tokens['auth_token'],
		] );

		$I->seeHttpHeader( 'X-WPGraphQL-Login-Token' );
		$I->seeHttpHeader( 'X-WPGraphQL-Login-Refresh-Token' );


		// The query is valid and has no errors.
		$I->assertArrayNotHasKey( 'errors', $response );
		$I->assertEmpty( $response['extensions']['debug'] );

		// The response is properly returning data as expected.
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

	public function testQueryWithInvalidHeaders( FunctionalTester $I ) {
		$I->wantTo( 'Query with invalid authentication headers.' );

		$user_id = $I->haveUserInDatabase( 'testuser', 'administrator', [ 'user_pass' => 'testpass' ] );

		$I->haveGraphQLDebug();

		$auth_token = 'invalid-auth-token';

		$post_id = $I->havePostInDatabase( [
			'post_title'   => 'Test Post',
			'post_type'    => 'post',
			'post_status'  => 'publish',
			'post_content' => 'Post Content',
		] );

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


		$I->sendPOST(
			// Use site url.
			get_site_url( null, '/graphql' ),
			json_encode(
				[
					'query' => $query
				],
			)
		);

		// Check response.
		$I->seeResponseCodeIs( 403 );
		$I->dontSeeHttpHeader( 'X-WPGraphQL-Login-Token' );
		$I->dontSeeHttpHeader( 'X-WPGraphQL-Login-Refresh-Token' );
		$I->seeResponseIsJson();

		$response       = $I->grabResponse();
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

	protected function generate_tokens( $user_id, FunctionalTester $I ) : array {
		wp_set_current_user( $user_id );
		$site_secret = wp_generate_password( 64, false, false );
		update_option( PluginSettings::$settings_prefix . 'jwt_secret_key', $site_secret );
		TokenManager::issue_new_user_secret( $user_id, false );
		$I->reset_utils_properties();
		$auth_token = TokenManager::get_auth_token( wp_get_current_user(), false );
		$I->reset_utils_properties();
		$refresh_token = TokenManager::get_refresh_token( wp_get_current_user(), false );
		wp_set_current_user( 0 );
		$I->reset_utils_properties();

		return [
			'site_secret' => $site_secret,
			'auth_token' => $auth_token,
			'refresh_token' => $refresh_token,
		];
	}
}
