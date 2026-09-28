<?php
/**
 * Tests the revokeUserSecret mutation.
 *
 * @package WPGraphQL\Login\Tests\Integration\GraphQL\Type\Mutation
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Tests\Integration\GraphQL\Type\Mutation;

use PHPUnit\Framework\Attributes\CoversClass;
use WPGraphQL\Login\GraphQL\Type\Mutation\RevokeUserSecret;
use WPGraphQL\Login\Tests\TestCase;

/**
 * Tests the revokeUserSecret mutation.
 */
#[CoversClass( RevokeUserSecret::class )]
class RevokeUserSecretTest extends TestCase {
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
	 * Returns the revokeUserSecret mutation.
	 */
	private function query(): string {
		return '
			mutation RevokeUserSecret( $userId: ID! ) {
				revokeUserSecret(input: {userId: $userId}) {
					revokedUserSecret
					success
				}
			}
		';
	}

	/**
	 * Tests that an unauthenticated user cannot revoke a user's secret.
	 */
	public function test_revoke_user_secret_rejects_unauthorized_user(): void {
		$query = $this->query();

		$variables = [
			'userId' => $this->test_user,
		];

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertEquals( 'You are not allowed to revoke the user secret.', $actual['errors'][0]['message'] );
	}

	/**
	 * Tests that revoking the secret of a nonexistent user is rejected.
	 */
	public function test_revoke_user_secret_rejects_nonexistent_user(): void {
		$query = $this->query();

		$variables = [
			'userId' => 999999,
		];

		// Set as admin.
		wp_set_current_user( $this->admin );

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertEquals( 'You are not allowed to revoke the user secret.', $actual['errors'][0]['message'] );
	}

	/**
	 * Tests that an admin can revoke a user's secret, but only the user receives the revoked secret.
	 */
	public function test_revoke_user_secret_only_returns_revoked_secret_to_the_user(): void {
		$query = $this->query();

		// Test as admin user.
		wp_set_current_user( $this->admin );

		$variables = [
			'userId' => $this->test_user,
		];

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertTrue( $actual['data']['revokeUserSecret']['success'] );
		$this->assertNull( $actual['data']['revokeUserSecret']['revokedUserSecret'] );

		// Test as actual user.
		wp_set_current_user( $this->test_user );

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertTrue( $actual['data']['revokeUserSecret']['success'] );
		$this->assertNotNull( $actual['data']['revokeUserSecret']['revokedUserSecret'] );
	}
}
