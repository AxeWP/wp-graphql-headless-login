<?php
/**
 * Tests the refreshUserSecret mutation.
 *
 * @package Tests\WPGraphQL\Login\Integration\Auth
 */

namespace Tests\WPGraphQL\Login\Integration\Auth;

use Tests\WPGraphQL\Login\TestCase;

/**
 * Tests the refreshUserSecret mutation.
 */
class RefreshUserSecretMutationTest extends TestCase {
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
	 * {@inheritDoc}
	 */
	public function setUp(): void {
		parent::setUp();

		$this->admin = $this->factory()->user->create(
			[
				'role' => 'administrator',
			]
		);

		$this->test_user = $this->factory()->user->create(
			[
				'role' => 'subscriber',
			]
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function tearDown(): void {
		$this->reset_utils_properties();
		$this->clearSchema();

		parent::tearDown();
	}

	/**
	 * The `refreshUserSecret` mutation.
	 */
	public function query(): string {
		return '
			mutation RefreshUserSecret( $userId: ID! ) {
				refreshUserSecret(input: {userId: $userId}) {
					authToken
					refreshToken
					revokedUserSecret
					success
					userSecret
				}
			}
		';
	}

	/**
	 * Tests the mutation without the required permissions.
	 */
	public function testWithBadPermissions(): void {
		$query = $this->query();

		$variables = [
			'userId' => $this->test_user,
		];

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertEquals( 'You are not allowed to refresh the user secret.', $actual['errors'][0]['message'] );
	}

	/**
	 * Tests the mutation with an invalid user ID.
	 */
	public function testWithBadId(): void {
		$query = $this->query();

		$variables = [
			'userId' => 999999,
		];

		// Set as admin
		wp_set_current_user( $this->admin );

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertEquals( 'You are not allowed to refresh the user secret.', $actual['errors'][0]['message'] );
	}

	/**
	 * Tests the mutation with a valid request.
	 */
	public function testMutation(): void {
		$query = $this->query();

		// Test as admin user
		wp_set_current_user( $this->admin );

		$variables = [
			'userId' => $this->test_user,
		];

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertTrue( $actual['data']['refreshUserSecret']['success'] );
		$this->assertNull( $actual['data']['refreshUserSecret']['authToken'] );
		$this->assertNull( $actual['data']['refreshUserSecret']['refreshToken'] );
		$this->assertNull( $actual['data']['refreshUserSecret']['revokedUserSecret'] );
		$this->assertNull( $actual['data']['refreshUserSecret']['userSecret'] );

		// Test as actual user
		wp_set_current_user( $this->test_user );

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertTrue( $actual['data']['refreshUserSecret']['success'] );
		$this->assertNotNull( $actual['data']['refreshUserSecret']['authToken'] );
		$this->assertNotNull( $actual['data']['refreshUserSecret']['refreshToken'] );
		$this->assertNotNull( $actual['data']['refreshUserSecret']['revokedUserSecret'] );
		$this->assertNotNull( $actual['data']['refreshUserSecret']['userSecret'] );
	}
}
