<?php
/**
 * Tests the logout mutation.
 *
 * @package WPGraphQL\Login\Tests\Integration\GraphQL\Type\Mutation
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Tests\Integration\GraphQL\Type\Mutation;

use PHPUnit\Framework\Attributes\CoversClass;
use WPGraphQL\Login\GraphQL\Type\Mutation\Logout;
use WPGraphQL\Login\Settings\AccessControlSettings;
use WPGraphQL\Login\Settings\CookieSettings;
use WPGraphQL\Login\Tests\TestCase;

/**
 * Tests the logout mutation.
 */
#[CoversClass( Logout::class )]
class LogoutTest extends TestCase {
	/**
	 * The administrator user ID.
	 */
	private int $admin;

	/**
	 * {@inheritDoc}
	 */
	protected function setUp(): void {
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
	protected function tearDown(): void {
		unset( $_SERVER['HTTP_ORIGIN'] );
		$this->reset_utils_properties();

		delete_option( AccessControlSettings::get_slug() );
		delete_option( CookieSettings::get_slug() );

		$this->clearSchema();

		parent::tearDown();
	}

	/**
	 * Returns the logout mutation.
	 */
	private function query(): string {
		return '
			mutation Logout {
				logout( input: {} ){
					success
				}
			}
		';
	}

	/**
	 * Tests that the mutation is only registered when it and all its dependencies are enabled.
	 */
	public function test_logout_mutation_is_only_registered_when_all_dependencies_are_enabled(): void {
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

	/**
	 * Tests that the mutation cannot be queried when it is disabled.
	 */
	public function test_logout_mutation_cannot_be_queried_when_disabled(): void {
		$query = $this->query();

		$actual = $this->graphql( compact( 'query' ) );

		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertStringStartsWith( 'Cannot query field "logout" on type "RootMutation".', $actual['errors'][0]['message'] );
	}

	/**
	 * Tests that the mutation logs out the current user, and returns null when no user is logged in.
	 */
	public function test_logout_mutation_logs_out_the_current_user(): void {
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

		// Test as admin user.
		wp_set_current_user( $this->admin );

		$actual = $this->graphql( compact( 'query' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertTrue( $actual['data']['logout']['success'], 'The success field should be true if the user is logged out.' );
		$this->assertFalse( is_user_logged_in(), 'The user should be logged out.' );
	}
}
