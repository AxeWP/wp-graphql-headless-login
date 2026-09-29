<?php
/**
 * Tests the refreshUserSecret mutation.
 *
 * @package WPGraphQL\Login\Tests\Integration\GraphQL\Type\Mutation
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Tests\Integration\GraphQL\Type\Mutation;

use PHPUnit\Framework\Attributes\CoversClass;
use WPGraphQL\Login\GraphQL\Type\Mutation\RefreshUserSecret;
use WPGraphQL\Login\Tests\TestCase;

/**
 * Tests the refreshUserSecret mutation.
 */
#[CoversClass( RefreshUserSecret::class )]
class RefreshUserSecretTest extends TestCase {
	/**
	 * The administrator user ID.
	 */
	private int $admin;

	/**
	 * The test user ID.
	 */
	private int $test_user;

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

		$this->test_user = $this->factory()->user->create(
			[
				'role' => 'subscriber',
			]
		);
	}

	/**
	 * {@inheritDoc}
	 */
	protected function tearDown(): void {
		$this->reset_utils_properties();
		$this->clearSchema();

		parent::tearDown();
	}

	/**
	 * Returns the refreshUserSecret mutation.
	 */
	private function query(): string {
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
	 * Tests that an unauthenticated user cannot refresh a user's secret.
	 */
	public function test_refresh_user_secret_rejects_unauthorized_user(): void {
		$query = $this->query();

		$variables = [
			'userId' => $this->test_user,
		];

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertEquals( 'You are not allowed to refresh the user secret.', $actual['errors'][0]['message'] );
	}

	/**
	 * Tests that refreshing the secret of a nonexistent user is rejected.
	 */
	public function test_refresh_user_secret_rejects_nonexistent_user(): void {
		$query = $this->query();

		$variables = [
			'userId' => 999999,
		];

		// Set as admin.
		wp_set_current_user( $this->admin );

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertEquals( 'You are not allowed to refresh the user secret.', $actual['errors'][0]['message'] );
	}

	/**
	 * Tests that an admin can refresh a user's secret, but only the user receives the new secret and tokens.
	 */
	public function test_refresh_user_secret_only_returns_new_credentials_to_the_user(): void {
		$query = $this->query();

		// Test as admin user.
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

		// Test as actual user.
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
