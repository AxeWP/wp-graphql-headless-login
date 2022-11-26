<?php

use WPGraphQL\Login\Admin\Settings;
use WPGraphQL\Login\Auth\TokenManager;

$I = new FunctionalTester( $scenario );
$I->wantTo( 'Query with valid authentication headers' );

$user_id = $I->haveUserInDatabase( 'testuser', 'administrator', [ 'user_pass' => 'testpass' ] );

// Enable graphql debug
$graphql_settings = get_option( 'graphql_general_settings', [] );
$graphql_settings[ 'debug_mode_enabled' ] = 'on';
update_option( 'graphql_general_settings', $graphql_settings );


// Generate an auth and refresh token for the user.
wp_set_current_user( $user_id );
$site_secret = wp_generate_password( 64, false, false );
update_option( Settings::$settings_prefix . 'jwt_secret_key', $site_secret );
TokenManager::issue_new_user_secret( $user_id, false );
$I->reset_utils_properties();
$auth_token = TokenManager::get_auth_token( wp_get_current_user(), false );
$I->reset_utils_properties();
$refresh_token = TokenManager::get_refresh_token( wp_get_current_user(), false );
wp_set_current_user( 0 );
$I->reset_utils_properties();


// Set the content-type so we get a proper response from the API.
$I->haveHttpHeader( 'Content-Type', 'application/json' );
$I->setHeader( 'Authorization', 'Bearer ' . $auth_token );

$I->sendPOST(
	// Use site url.
	get_site_url( null, '/graphql' ),
	json_encode(
		[
			'query' => 'query {
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
$I->seeResponseCodeIs( 200 );

// $I->seeHttpHeader( 'X-WPGraphQL-Login-Token' );
// $I->seeHttpHeader( 'X-WPGraphQL-Login-Refresh-Token' );

$I->seeResponseIsJson();

$response       = $I->grabResponse();
$response_array = json_decode( $response, true );

// The query is valid and has no errors.
$I->assertArrayNotHasKey( 'errors', $response_array );
$I->assertEmpty( $response_array['extensions']['debug'] );

// The response is properly returning data as expected.
$I->assertArrayHasKey( 'data', $response_array );
$I->assertEquals( $user_id, $response_array['data']['viewer']['databaseId'] );
$I->assertEquals( 'testuser', $response_array['data']['viewer']['username'] );
$I->assertNotEmpty( $response_array['data']['viewer']['auth']['authToken'] );
$I->assertNotEmpty( $response_array['data']['viewer']['auth']['authTokenExpiration'] );
$I->assertNotEmpty( $response_array['data']['viewer']['auth']['refreshToken'] );
$I->assertNotEmpty( $response_array['data']['viewer']['auth']['refreshTokenExpiration'] );
$I->assertFalse( $response_array['data']['viewer']['auth']['isUserSecretRevoked'] );
$I->assertNotEmpty( $response_array['data']['viewer']['auth']['userSecret'] );

