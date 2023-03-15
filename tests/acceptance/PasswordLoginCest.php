<?php

use WPGraphQL\Login\Admin\Settings\PluginSettings;

class PasswordLoginCest {
	public function _before( AcceptanceTester $I ) {
		$I->set_client_config(
			'password',
			[
				'name'          => 'Password',
				'slug'          => 'password',
				'order'         => 0,
				'isEnabled'     => true,
				'clientOptions' => [],
				'loginOptions'  => [
					'useAuthenticationCookie' => true,
				],
			]
		);
	}

	public function testMutation( AcceptanceTester $I ) {
		$I->wantTo( 'Test the PASSWORD provider with 3rd party plugin data' );

		// Setup the user.
		$user_id = $I->haveUserInDatabase( 'testuser', 'administrator', [ 'user_pass' => 'testpass' ] );

		$I->haveGraphQLDebug();
		$expected_tokens = $I->generate_user_tokens( $user_id );

		$query = '
			mutation LoginWithPassword( $username: String! $password: String!) {
				login( input: { credentials: { username: $username, password: $password }, provider: PASSWORD } ) {
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

		$variables = [
			'username' => 'testuser',
			'password' => 'testpass',
		];

		$response = $I->sendGraphQlRequest( $query, $variables );

		codecept_debug( $response );

		// The woo session is left intact.
		$I->seeHttpHeader( 'woocommerce-session' );

		// The query is valid and has no errors.
		$I->assertArrayNotHasKey( 'errors', $response );
		$I->assertEmpty( $response['extensions']['debug'] );

		// The response is properly returning data as expected.
		$I->assertArrayHasKey( 'data', $response );

		// Assert the wooSessionToken is the same as the header.
		$I->assertEquals( $I->grabHttpHeader( 'woocommerce-session' ), $response['data']['login']['wooSessionToken'] );

		// Assert the Woo customer data is the same as the user.
		$I->assertEquals( $user_id, $response['data']['login']['customer']['databaseId'] );
		$I->assertEquals( $response['data']['login']['user']['auth']['userSecret'], $response['data']['login']['customer']['auth']['userSecret'] );

		$cookies = $I->grabCookiesWithPattern( '/^wordpress_logged_in_/' );

		/** @var \Symfony\Component\BrowserKit\Cookie */
		foreach ( $cookies as $cookie ) {
			$parsed_cookie = wp_parse_auth_cookie( $cookie->getValue(), 'logged_in' );
			$I->assertNotEmpty( $parsed_cookie );
		}

		// Test token

		wp_set_current_user( 0 );

		// Query with the auth token.
		$auth_token = $response['data']['login']['authToken'];

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
		delete_option( PluginSettings::$settings_prefix . 'jwt_secret_key' );
	}
}
