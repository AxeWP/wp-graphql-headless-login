<?php

use WPGraphQL\Login\Admin\Settings\PluginSettings;
use WPGraphQL\Login\Auth\TokenManager;

$I = new AcceptanceTester( $scenario );

// Setup the user.
$user_id = $I->haveUserInDatabase( 'testuser', 'administrator', [ 'user_pass' => 'testpass' ] );

// Enable GraphQL Debug.
$I->haveGraphQLDebug();

// Enable the the password mutation and cookies.
update_option( PluginSettings::$settings_prefix . 'jwt_secret_key', wp_generate_password( 64, false, false ) );
TokenManager::issue_new_user_secret( $user_id, false );
update_option( PluginSettings::$settings_prefix . 'enable_password_mutation', true );
update_option( PluginSettings::$settings_prefix . 'password_use_auth_cookie', true );
$I->reset_utils_properties();

$query = '
	mutation LoginWithPassword( $input: LoginWithPasswordInput! ) {
		loginWithPassword(
			input: $input
		) {
			authToken
			authTokenExpiration
			refreshToken
			refreshTokenExpiration
			user {
				auth {
					isUserSecretRevoked
					linkedIdentities {
						id
						provider
					}
					userSecret
					wooSessionToken
				}
				databaseId
				username
			}
			# Woocommerce Data
			wooSessionToken
			customer {
				databaseId
				auth {
					userSecret
				}
			}
		}
	}
';

$input = [
	'username' => 'testuser',
	'password' => 'testpass',
];

$response = $I->sendGraphQlRequest( $query, $input );

// The woo session is left intact.
$I->seeHttpHeader( 'woocommerce-session' );

// The query is valid and has no errors.
$I->assertArrayNotHasKey( 'errors', $response );
$I->assertEmpty( $response['extensions']['debug'] );

// The response is properly returning data as expected.
$I->assertArrayHasKey( 'data', $response );

// Assert the wooSessionToken is the same as the header
$I->assertEquals( $I->grabHttpHeader( 'woocommerce-session' ), $response['data']['loginWithPassword']['wooSessionToken'] );

// Assert the Woo customer data is the same as the user.
$I->assertEquals( $user_id, $response['data']['loginWithPassword']['customer']['databaseId'] );
$I->assertEquals( $response['data']['loginWithPassword']['user']['auth']['userSecret'], $response['data']['loginWithPassword']['customer']['auth']['userSecret'] );

$cookies = $I->grabCookiesWithPattern( '/^wordpress_logged_in_/' );

/** @var \Symfony\Component\BrowserKit\Cookie */
foreach ( $cookies as $cookie ) {
	$parsed_cookie = wp_parse_auth_cookie( $cookie->getValue(), 'logged_in' );
	$I->assertNotEmpty( $parsed_cookie );
}

wp_set_current_user( 0 );

// Query with the auth token.
$auth_token = $response['data']['loginWithPassword']['authToken'];

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
			wooSessionToken
		}
	}
}';

wp_set_current_user( 0 );

$I->reset_utils_properties();

$response = $I->sendGraphQLRequest( $query, null, [ 'Authorization' => 'Bearer ' . $auth_token ] );

// Assert the auth and refresh tokens are set and the same.
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
$I->assertNotEmpty( $response['data']['viewer']['auth']['wooSessionToken'] );

// Cleanup
wp_delete_user( $user_id );
delete_option( PluginSettings::$settings_prefix . 'jwt_secret_key' );
