<?php

use WPGraphQL\Login\Admin\Settings\AccessControlSettings;
use WPGraphQL\Login\Admin\Settings\CookieSettings;

/**
 * Tests the logout mutation.
 */
class LogoutMutation extends \Tests\WPGraphQL\TestCase\WPGraphQLTestCase {
	public $tester;
	public $admin;
	public $test_user;
	public $auth_token;
	public $refresh_token;

	/**
	 * {@inheritDoc}
	 */
	public function setUp(): void {
		parent::setUp();

		$this->admin = $this->factory()->user->create(
			[
				'role' => 'administrator',
			]
		);

		delete_option( AccessControlSettings::get_slug() );
		delete_option( CookieSettings::get_slug() );

		$_SERVER['HTTP_ORIGIN'] = site_url();

		$this->clearSchema();
	}

	/**
	 * {@inheritDoc}
	 */
	public function tearDown(): void {
		$this->tester->reset_utils_properties();

		delete_option( AccessControlSettings::get_slug() );
		delete_option( CookieSettings::get_slug() );

		$this->clearSchema();

		parent::tearDown();
	}

	public function query(): string {
		return '
			mutation Logout {
				logout( input: {} ){
					success
				}
			}
		';
	}

	public function testSchema(): void {
		// Test with mutation disabled.
		$query = '
			query {
				__type(name: "RootMutation") {
					fields {
						name
					}
				}
			}
		';

		$actual = $this->graphql( compact( 'query' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertNotContains( 'logout', wp_list_pluck( $actual['data']['__type']['fields'], 'name' ), 'Logout mutation should not be exposed.' );

		// Test with mutation enabled.
		update_option( CookieSettings::get_slug(), [ 'hasLogoutMutation' => true ] );

		$this->clearSchema();

		$actual = $this->graphql( compact( 'query' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertNotContains( 'logout', wp_list_pluck( $actual['data']['__type']['fields'], 'name' ), 'Logout mutation should not be exposed.' );

		// Test with mutation and dependency enabled.
		update_option(
			CookieSettings::get_slug(),
			[
				'hasLogoutMutation'                => true,
				'hasAccessControlAllowCredentials' => true,
			]
		);

		$this->clearSchema();

		$actual = $this->graphql( compact( 'query' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertNotContains( 'logout', wp_list_pluck( $actual['data']['__type']['fields'], 'name' ), 'Logout mutation should not be exposed.' );

		// Test with ALL dependencies enabled.
		update_option( AccessControlSettings::get_slug(), [ 'shouldBlockUnauthorizedDomains' => true ] );

		$this->clearSchema();

		$actual = $this->graphql( compact( 'query' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertContains( 'logout', wp_list_pluck( $actual['data']['__type']['fields'], 'name' ), 'Logout mutation should be exposed.' );
	}

	public function testWithMutationDisabled(): void {
		$query = $this->query();

		// Test with mutation disabled.
		$actual = $this->graphql( compact( 'query' ) );

		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertStringStartsWith( 'Cannot query field "logout" on type "RootMutation".', $actual['errors'][0]['message'] );
	}

	public function testWithMutationEnabled(): void {
		update_option(
			CookieSettings::get_slug(),
			[
				'hasLogoutMutation'                => true,
				'hasAccessControlAllowCredentials' => true,
			]
		);
		update_option( AccessControlSettings::get_slug(), [ 'shouldBlockUnauthorizedDomains' => true ] );

		$query = $this->query();

		$actual = $this->graphql( compact( 'query' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );

		$this->assertNull( $actual['data']['logout']['success'], 'The success field should be null if the user is not logged in.' );

		// Test as admin user
		wp_set_current_user( $this->admin );

		$actual = $this->graphql( compact( 'query' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertTrue( $actual['data']['logout']['success'], 'The success field should be true if the user is logged out.' );
		$this->assertFalse( is_user_logged_in(), 'The user should be logged out.' );
	}
}
