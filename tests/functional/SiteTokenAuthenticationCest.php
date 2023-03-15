<?php

use WPGraphQL\Login\Admin\Settings\AccessControlSettings;

class SiteTokenAuthenticationCest {

	public function _before( AcceptanceTester $I ) {
		$I->set_client_config(
			'siteToken',
			[
				'name'          => 'Site Token',
				'slug'          => 'siteToken',
				'order'         => 0,
				'isEnabled'     => true,
				'clientOptions' => [
					'headerKey' => 'X-My-Secret-Auth-Token',
					'secretKey' => 'some_secret',
				],
				'loginOptions'  => [
					'useAuthenticationCookie' => true,
					'metaKey'                 => 'email',
				],
			]
		);
		$I->reset_utils_properties();
		update_option( AccessControlSettings::$settings_prefix . 'access_control', [] );
	}

	public function testLoginWithSiteToken( FunctionalTester $I ) {
		$I->wantTo( 'Test the Site Token provider.' );

		$user_id = $I->haveUserInDatabase(
			'testuser',
			'administrator',
			[
				'user_pass'  => 'testpass',
				'user_email' => 'some_email@test.com',
			]
		);

		$I->haveGraphQLDebug();

		$query = '
			mutation LoginWithSiteToken( $identity: String!) {
				login( input: { identity: $identity, provider: SITETOKEN } ) {
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
						}
						databaseId
						username
						email
					}
				}
			}
		';

		$variables = [
			'identity' => 'some_email@test.com',
		];

		$response = $I->sendGraphQLRequest(
			$query,
			$variables,
			[
				'X-My-Secret-Auth-Token' => 'some_secret',
			]
		);

		$I->dontSeeHttpHeader( 'X-My-Secret-Auth-Token' );

		// The query is valid and has no errors.
		$I->assertArrayNotHasKey( 'errors', $response );
		$I->assertEmpty( $response['extensions']['debug'] );

		// The response is properly returning data as expected.
		$I->assertArrayHasKey( 'data', $response );
		$I->assertNotEmpty( $response['data']['login']['authToken'] );
		$I->assertNotEmpty( $response['data']['login']['authTokenExpiration'] );
		$I->assertNotEmpty( $response['data']['login']['refreshToken'] );
		$I->assertNotEmpty( $response['data']['login']['refreshTokenExpiration'] );
		$I->assertEquals( 'testuser', $response['data']['login']['user']['username'] );
		$I->assertEquals( 'some_email@test.com', $response['data']['login']['user']['email'] );
	}
}
