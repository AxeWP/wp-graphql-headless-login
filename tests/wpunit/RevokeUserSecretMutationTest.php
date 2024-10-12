<?php


/**
 * Tests access functons
 */
class RevokeUserSecretMutationTest extends \Tests\WPGraphQL\TestCase\WPGraphQLTestCase {
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
		$this->tester->reset_utils_properties();
		$this->clearSchema();

		parent::tearDown();
	}

	public function query(): string {
		return '
			mutation RevokeUserSecret( $userId: ID! ) {
				revokeUserSecret(input: {userId: $userId}) {
					revokedUserSecret
					success
				}
			}
		';
	}

	public function testWithBadPermissions(): void {
		$query = $this->query();

		$variables = [
			'userId' => $this->test_user,
		];

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertEquals( 'You are not allowed to revoke the user secret.', $actual['errors'][0]['message'] );
	}

	public function testWithBadId(): void {
		$query = $this->query();

		$variables = [
			'userId' => 999999,
		];

		// Set as admin
		wp_set_current_user( $this->admin );

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertEquals( 'You are not allowed to revoke the user secret.', $actual['errors'][0]['message'] );
	}

	public function testMutation(): void {
		$query = $this->query();

		// Test as admin user
		wp_set_current_user( $this->admin );

		$variables = [
			'userId' => $this->test_user,
		];

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertTrue( $actual['data']['revokeUserSecret']['success'] );
		$this->assertNull( $actual['data']['revokeUserSecret']['revokedUserSecret'] );

		// Test as actual user
		wp_set_current_user( $this->test_user );

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertTrue( $actual['data']['revokeUserSecret']['success'] );
		$this->assertNotNull( $actual['data']['revokeUserSecret']['revokedUserSecret'] );
	}
}
