<?php
/**
 * Tests the CoreSchemaFilters class.
 *
 * @package WPGraphQL\Login\Tests\Integration\GraphQL
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Tests\Integration\GraphQL;

use GraphQL\Error\UserError;
use PHPUnit\Framework\Attributes\CoversClass;
use WPGraphQL\Login\Auth\TokenManager;
use WPGraphQL\Login\Auth\User;
use WPGraphQL\Login\GraphQL\CoreSchemaFilters;
use WPGraphQL\Login\Tests\TestCase;

/**
 * Tests CoreSchemaFilters.
 */
#[CoversClass( CoreSchemaFilters::class )]
class CoreSchemaFiltersTest extends TestCase {
	/**
	 * The CoreSchemaFilters instance.
	 */
	private CoreSchemaFilters $filters;

	/**
	 * {@inheritDoc}
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->filters = new CoreSchemaFilters();
	}

	/**
	 * Tests that a token is not returned for a user whose secret is revoked.
	 */
	public function test_check_if_secret_is_revoked_throws_when_secret_is_revoked(): void {
		$user_id = $this->factory()->user->create();

		User::set_is_secret_revoked( $user_id, true );

		$this->expectException( UserError::class );
		$this->filters->check_if_secret_is_revoked( 'test_token', $user_id );
	}

	/**
	 * Tests that the token is returned unchanged for a user whose secret is not revoked.
	 */
	public function test_check_if_secret_is_revoked_returns_token_when_secret_is_active(): void {
		$user_id = $this->factory()->user->create();

		$expected = 'test_token';

		TokenManager::refresh_user_secret( $user_id, false );

		$actual = $this->filters->check_if_secret_is_revoked( $expected, $user_id );

		$this->assertEquals( $expected, $actual );
	}
}
