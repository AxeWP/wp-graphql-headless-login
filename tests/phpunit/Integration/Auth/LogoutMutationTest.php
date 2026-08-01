<?php
/**
 * Tests the logout mutation.
 *
 * @package Tests\WPGraphQL\Login\Integration\Auth
 */

namespace Tests\WPGraphQL\Login\Integration\Auth;

use Tests\WPGraphQL\Login\TestCase;
use WPGraphQL\Login\Admin\Settings\AccessControlSettings;
use WPGraphQL\Login\Admin\Settings\CookieSettings;

/**
 * Tests the logout mutation.
 */
class LogoutMutationTest extends TestCase {
	/**
	 * The administrator user ID.
	 *
	 * @var int
	 */
	public $admin;

	/**
	 * The test user ID.
	 *
	 * @var int
	 */
	public $test_user;

	/**
	 * The auth token for the test user.
	 *
	 * @var string
	 */
	public $auth_token;

	/**
	 * The refresh token for the test user.
	 *
	 * @var string
	 */
	public $refresh_token;

	/**
	 * Returns the `name` of each of the given fields.
	 */
	private function getFieldNames( array $fields ): array {
		return call_user_func( 'wp_list_pluck', $fields, 'name' );
	}

	/**
	 * Wrapper for `is_user_logged_in()`.
	 */
	private function isUserLoggedIn(): bool {
		return (bool) call_user_func( 'is_user_logged_in' );
	}

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
		$this->reset_utils_properties();

		delete_option( AccessControlSettings::get_slug() );
		delete_option( CookieSettings::get_slug() );

		$this->clearSchema();

		parent::tearDown();
	}

	/**
	 * The `logout` mutation.
	 */
	public function query(): string {
		return '
			mutation Logout {
				logout( input: {} ){
					success
				}
			}
		';
	}

	/**
	 * Tests that the mutation is only registered when it is enabled.
	 */
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
		$this->assertNotContains( 'logout', $this->getFieldNames( $actual['data']['__type']['fields'] ), 'Logout mutation should not be exposed.' );

		// Test with mutation enabled.
		update_option( CookieSettings::get_slug(), [ 'hasLogoutMutation' => true ] );

		$this->clearSchema();

		$actual = $this->graphql( compact( 'query' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertNotContains( 'logout', $this->getFieldNames( $actual['data']['__type']['fields'] ), 'Logout mutation should not be exposed.' );

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
		$this->assertNotContains( 'logout', $this->getFieldNames( $actual['data']['__type']['fields'] ), 'Logout mutation should not be exposed.' );

		// Test with ALL dependencies enabled.
		update_option( AccessControlSettings::get_slug(), [ 'shouldBlockUnauthorizedDomains' => true ] );

		$this->clearSchema();

		$actual = $this->graphql( compact( 'query' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertContains( 'logout', $this->getFieldNames( $actual['data']['__type']['fields'] ), 'Logout mutation should be exposed.' );
	}

	/**
	 * Tests the mutation when `hasLogoutMutation` is disabled.
	 */
	public function testWithMutationDisabled(): void {
		$query = $this->query();

		// Test with mutation disabled.
		$actual = $this->graphql( compact( 'query' ) );

		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertStringStartsWith( 'Cannot query field "logout" on type "RootMutation".', $actual['errors'][0]['message'] );
	}

	/**
	 * Tests the mutation when `hasLogoutMutation` is enabled.
	 */
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
		$this->assertFalse( $this->isUserLoggedIn(), 'The user should be logged out.' );
	}
}
