<?php

use WPGraphQL\Login\Admin\Settings\AccessControlSettings;
use WPGraphQL\Login\Admin\Settings\ProviderSettings;

class SiteTokenAuthenticationCest {
	public function _before( FunctionalTester $I ) {
		$I->haveOptionInDatabase(
			ProviderSettings::$settings_prefix . 'siteToken',
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
		$I->haveOptionInDatabase( AccessControlSettings::get_slug() . 'access_control', [] );
		$I->reset_utils_properties();
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

		// The query has errors because the mutation is not allowed.

		$I->haveOptionInDatabase(
			AccessControlSettings::get_slug(),
			[
				'shouldBlockUnauthorizedDomains' => true,
				'hasSiteAddressInOrigin'         => true,
			]
		);
		$I->reset_utils_properties();

		$response = $I->sendGraphQLRequest(
			$query,
			$variables,
			[
				'X-My-Secret-Auth-Token' => 'some_secret',
			]
		);

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

		$cookies = $I->grabCookiesWithPattern( '/^wordpress_logged_in_/' );

		/** @var \Symfony\Component\BrowserKit\Cookie */
		foreach ( $cookies as $cookie ) {
			$parsed_cookie = wp_parse_auth_cookie( $cookie->getValue(), 'logged_in' );
			$I->assertNotEmpty( $parsed_cookie );
		}
	}
}
