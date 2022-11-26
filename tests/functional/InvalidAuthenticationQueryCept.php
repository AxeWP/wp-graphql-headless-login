<?php

$I = new FunctionalTester( $scenario );
$I->wantTo( 'Query with invalid authentication headers.' );

$user_id = $I->haveUserInDatabase( 'testuser', 'administrator', [ 'user_pass' => 'testpass' ] );

$graphql_settings = get_option( 'graphql_general_settings', [] );
$graphql_settings[ 'debug_mode_enabled' ] = 'on';
update_option( 'graphql_general_settings', $graphql_settings );

// Generate an auth and refresh token for the user.
$auth_token = 'invalid-auth-token';

$post_id = $I->havePostInDatabase(
	[
		'post_title'   => 'Test Post',
		'post_type'    => 'post',
		'post_status'  => 'publish',
		'post_content' => 'Post Content',
	]
);


// Set the content-type so we get a proper response from the API.
$I->haveHttpHeader( 'Content-Type', 'application/json' );
$I->setHeader( 'Authorization', 'Bearer ' . $auth_token );

$I->sendPOST(
	// Use site url.
	get_site_url( null, '/graphql' ),
	json_encode(
		[
			'query' => 'query {
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
			}',
		],
	)
);

// Check response.
$I->seeResponseCodeIs( 403 );
$I->dontSeeHttpHeader( 'X-WPGraphQL-Login-Token' );
$I->dontSeeHttpHeader( 'X-WPGraphQL-Login-Refresh-Token' );
$I->seeResponseIsJson();

$response       = $I->grabResponse();
$response_array = json_decode( $response, true );

// The response has authentication errors.
$I->assertEquals( 'invalid-secret-key | Wrong number of segments', $response_array['extensions']['debug'][0]['message'] );

// The response also has valid data.
$I->assertArrayHasKey( 'data', $response_array );

// The viewer is not authenticated.
$I->assertArrayHasKey( 'viewer', $response_array['data'] );
$I->assertNull( $response_array['data']['viewer'] );

// The posts are still returned.
$I->assertArrayHasKey( 'posts', $response_array['data'] );
$I->assertNotEmpty( $response_array['data']['posts']['edges'][0]['node']['id'] );
$I->assertNotEmpty( $response_array['data']['posts']['edges'][0]['node']['title'] );
$I->assertNotEmpty( $response_array['data']['posts']['edges'][0]['node']['link'] );
$I->assertNotEmpty( $response_array['data']['posts']['edges'][0]['node']['date'] );
