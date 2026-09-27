<?php
/**
 * Tests the CoreSchemaFilters class.
 *
 * @package Tests\WPGraphQL\Login\Integration\GraphQL
 */

namespace Tests\WPGraphQL\Login\Integration\GraphQL;

use Tests\WPGraphQL\Login\TestCase;
use WPGraphQL\Login\Auth\TokenManager;
use WPGraphQL\Login\Auth\User;
use WPGraphQL\Login\GraphQL\CoreSchemaFilters;

/**
 * Tests CoreSchemaFilters.
 */
class CoreSchemaFiltersTest extends TestCase {
	/**
	 * The administrator user ID.
	 *
	 * @var int
	 */
	public $admin;

	/**
	 * The CoreSchemaFilters instance.
	 *
	 * @var \WPGraphQL\Login\GraphQL\CoreSchemaFilters
	 */
	public $filters;

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

		$this->filters = new CoreSchemaFilters();
	}

	/**
	 * Tests check_if_secret_is_revoked() when the secret is revoked.
	 */
	public function testCheckIfSecretIsRevokedWhenRevoked(): void {
		$user_id = $this->factory()->user->create();

		$expected = 'test_token';

		User::set_is_secret_revoked( $user_id, true );

		$this->expectException( \GraphQL\Error\UserError::class );
		$this->filters->check_if_secret_is_revoked( $expected, $user_id );
	}

	/**
	 * Tests check_if_secret_is_revoked()
	 */
	public function testCheckIfSecretIsRevoked(): void {
		$user_id = $this->factory()->user->create();

		$expected = 'test_token';

		TokenManager::refresh_user_secret( $user_id, false );

		$actual = $this->filters->check_if_secret_is_revoked( $expected, $user_id );

		$this->assertEquals( $expected, $actual );
	}
}
