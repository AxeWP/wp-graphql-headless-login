<?php

use WPGraphQL\Login\Admin\Settings\AccessControlSettings;
use WPGraphQL\Login\Admin\Settings\CookieSettings;
use WPGraphQL\Login\Admin\Settings\ProviderSettings;

class PasswordLoginCest {
	public function _before( FunctionalTester $I ) {
		$I->haveOptionInDatabase(
			ProviderSettings::$settings_prefix . 'password',
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

		$I->haveOptionInDatabase(
			AccessControlSettings::get_slug(),
			[
				'shouldBlockUnauthorizedDomains' => true,
				'hasSiteAddressInOrigin'         => true,
				'additionalAuthorizedDomains'    => [
					'https://example.com',
				],
			]
		);

		$I->haveOptionInDatabase(
			CookieSettings::get_slug(),
			[
				'hasLogoutMutation'                => true,
				'hasAccessControlAllowCredentials' => true,
			]
		);

		$I->reset_utils_properties();

		if ( ! empty( $cookies ) ) {
			/** @var \Symfony\Component\BrowserKit\Cookie */
			foreach ( $cookies as $cookie ) {
				$I->deleteCookie( $cookie->getName() );
			}
		}
	}

	private function get_login_mutation(): string {
		return '
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
						}
						databaseId
						username
					}
				}
			}
		';
	}

	private function get_logout_mutation(): string {
		return '
			mutation Logout {
				logout( input: {} ){
					success
				}
			}
		';
	}

	private function get_query(): string {
		return '
			query {
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
	}

	public function testCookieAuth( FunctionalTester $I ) {
		$I->wantTo( 'Test the PASSWORD provider with cookie-based authentication' );

		// Setup the user.
		$user_id = $I->haveUserInDatabase( 'testuser', 'administrator', [ 'user_pass' => 'testpass' ] );

		$I->haveGraphQLDebug();
		$I->generateUserTokens( $user_id );

		$query = $this->get_login_mutation();

		$variables = [
			'username' => 'testuser',
			'password' => 'testpass',
		];

		$response = $I->sendGraphQlRequest(
			$query,
			$variables,
			[
				'Origin' => 'https://example.com',
			]
		);

		// The query is valid and has no errors.
		$I->assertArrayNotHasKey( 'errors', $response );
		$I->assertEmpty( $response['extensions']['debug'] );

		// The response is properly returning data as expected.
		$I->assertArrayHasKey( 'data', $response );

		$cookies = $I->grabCookiesWithPattern( '/^wordpress_logged_in_/' );

		/** @var \Symfony\Component\BrowserKit\Cookie */
		foreach ( $cookies as $cookie ) {
			$parsed_cookie = wp_parse_auth_cookie( $cookie->getValue(), 'logged_in' );
			$I->assertNotEmpty( $parsed_cookie );

			// Check the sameSite attribute.
			$I->assertEquals( 'Lax', $cookie->getSameSite() );
			// Check the domain attribute.
			$I->assertEquals( parse_url( get_home_url(), PHP_URL_HOST ), $cookie->getDomain() );
		}
		// Test an authenticated query with just the cookie.
		$I->reset_utils_properties();

		$query    = $this->get_query();
		$response = $I->sendGraphQLRequest( $query );

		// The query is valid and has no errors.
		$I->assertArrayNotHasKey( 'errors', $response );
		$I->assertEmpty( $response['extensions']['debug'] );

		// The response is properly returning data as expected.
		$I->assertArrayHasKey( 'data', $response );
		$I->assertEquals( $user_id, $response['data']['viewer']['databaseId'] );
		$I->assertEquals( 'testuser', $response['data']['viewer']['username'] );
		$I->assertNotEmpty( $response['data']['viewer']['auth']['authToken'] );
		$I->assertNotEmpty( $response['data']['viewer']['auth']['authTokenExpiration'] );

		// Logout before testing the strict sameSite attribute.
		$query = $this->get_logout_mutation();

		$response = $I->sendGraphQLRequest( $query );

		// The query is valid and has no errors.
		$I->assertArrayNotHasKey( 'errors', $response );
		$I->assertEmpty( $response['extensions']['debug'] );

		// The response is properly returning data as expected.
		$I->assertArrayHasKey( 'data', $response );
		$I->assertTrue( $response['data']['logout']['success'] );

		// Test the viewer query again.
		$I->reset_utils_properties();
		$query = $this->get_query();

		$response = $I->sendGraphQLRequest( $query );

		// The query is valid and has no errors.
		$I->assertArrayNotHasKey( 'errors', $response );
		$I->assertEmpty( $response['extensions']['debug'] );

		// The response is properly returning data as expected.
		$I->assertArrayHasKey( 'data', $response );
		$I->assertNull( $response['data']['viewer'] );

		// Test no cookies are set after logout.
		$cookies = $I->grabCookiesWithPattern( '/^wordpress_logged_in_/' );
		$I->assertEmpty( $cookies );

		// Test with strict sameSite attribute.
		$I->haveOptionInDatabase(
			CookieSettings::get_slug(),
			[
				'hasLogoutMutation'                => true,
				'hasAccessControlAllowCredentials' => true,
				'sameSiteOption'                   => 'Strict',
				'cookieDomain'                     => 'example.com',
			]
		);
		$I->reset_utils_properties();

		$query = $this->get_login_mutation();

		$variables = [
			'username' => 'testuser',
			'password' => 'testpass',
		];

		$response = $I->sendGraphQlRequest( $query, $variables );

		// The query is valid and has no errors.
		$I->assertArrayNotHasKey( 'errors', $response );
		$I->assertEmpty( $response['extensions']['debug'] );

		// The response is properly returning data as expected.
		$I->assertArrayHasKey( 'data', $response );
		$I->assertEquals( $user_id, $response['data']['login']['user']['databaseId'] );
		$I->assertEquals( 'testuser', $response['data']['login']['user']['username'] );

		$cookies = $I->grabCookiesWithPattern( '/^wordpress_logged_in_/' );
		/** @var \Symfony\Component\BrowserKit\Cookie */
		foreach ( $cookies as $cookie ) {
			$parsed_cookie = wp_parse_auth_cookie( $cookie->getValue(), 'logged_in' );
			$I->assertNotEmpty( $parsed_cookie );

			// Check the domain attribute.
			$I->assertEquals( 'example.com', $cookie->getDomain() );
			// Check the sameSite attribute.
			$I->assertEquals( 'Strict', $cookie->getSameSite() );
		}
	}

	public function testTokenAuth( FunctionalTester $I ) {
		$I->wantTo( 'Test token-based authentication' );

		$I->haveOptionInDatabase(
			ProviderSettings::$settings_prefix . 'password',
			[
				'name'          => 'Password',
				'slug'          => 'password',
				'order'         => 0,
				'isEnabled'     => true,
				'clientOptions' => [],
				'loginOptions'  => [
					'useAuthenticationCookie' => false,
				],
			]
		);

		// Setup the user.
		$user_id = $I->haveUserInDatabase( 'testuser', 'administrator', [ 'user_pass' => 'testpass' ] );

		$I->haveGraphQLDebug();
		$I->generateUserTokens( $user_id );

		$query = $this->get_login_mutation();

		$variables = [
			'username' => 'testuser',
			'password' => 'testpass',
		];

		$response = $I->sendGraphQlRequest(
			$query,
			$variables,
			[
				'Origin' => 'https://example.com',
			]
		);

		// The query is valid and has no errors.
		$I->assertArrayNotHasKey( 'errors', $response );
		$I->assertEmpty( $response['extensions']['debug'] );

		// The response is properly returning data as expected.
		$I->assertArrayHasKey( 'data', $response );

		// Stash the auth token for later use.
		$auth_token = $response['data']['login']['authToken'];

		// Test token
		wp_set_current_user( 0 );

		$query = $this->get_query();

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

		// Test logout with token.
		$logoutQuery    = $this->get_logout_mutation();
		$logoutResponse = $I->sendGraphQLRequest( $logoutQuery, null, [ 'Authorization' => 'Bearer ' . $auth_token ] );
		$I->assertArrayNotHasKey( 'errors', $logoutResponse );
		$I->assertTrue( $logoutResponse['data']['logout']['success'] );

		// Confirm cookie is not set.
		$cookies = $I->grabCookiesWithPattern( '/^wordpress_logged_in_/' );
		$I->assertEmpty( $cookies );
	}
}
