<?php
$I = new FunctionalTester( $scenario );
$I->wantTo( 'Get public data without authentication headers' );

$post_id = $I->havePostInDatabase(
	[
		'post_title'   => 'Test Post',
		'post_type'    => 'post',
		'post_status'  => 'publish',
		'post_content' => 'Post Content',
	]
);

$graphql_settings = get_option( 'graphql_general_settings', [] );
$graphql_settings[ 'debug_mode_enabled' ] = 'on';
update_option( 'graphql_general_settings', $graphql_settings );

// Set the content-type so we get a proper response from the API.
$I->haveHttpHeader( 'Content-Type', 'application/json' );

$I->sendPOST(
	// Use site url.
	get_site_url( null, '/graphql' ),
	json_encode(
		[
			'query' => '{ 
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
			}',
		]
	)
);

$I->seeResponseCodeIs( 200 );

$I->dontSeeHttpHeader( 'X-WPGraphQL-Login-Token' );
$I->dontSeeHttpHeader( 'X-WPGraphQL-Login-Refresh-Token' );

$I->seeResponseIsJson();
$response = $I->grabResponse();

$response_array = json_decode( $response, true );

// The query is valid and has no errors.
$I->assertArrayNotHasKey( 'errors', $response_array );
$I->assertEmpty( $response_array['extensions']['debug'] );

// The response is properly returning data as expected.
$I->assertArrayHasKey( 'data', $response_array );
$I->assertNotEmpty( $response_array['data']['posts']['edges'][0]['node']['id'] );
$I->assertNotEmpty( $response_array['data']['posts']['edges'][0]['node']['title'] );
$I->assertNotEmpty( $response_array['data']['posts']['edges'][0]['node']['link'] );
$I->assertNotEmpty( $response_array['data']['posts']['edges'][0]['node']['date'] );
