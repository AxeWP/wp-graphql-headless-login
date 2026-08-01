<?php
/**
 * Tests the CoreSchemaFilters class.
 *
 * @package Tests\WPGraphQL\Login\Integration\Settings
 */

namespace Tests\WPGraphQL\Login\Integration\Settings;

use Tests\WPGraphQL\Login\TestCase;
use WPGraphQL\Login\Auth\TokenManager;
use WPGraphQL\Login\Auth\User;
use WPGraphQL\Login\CoreSchemaFilters;

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
	 * {@inheritDoc}
	 */
	public function setUp(): void {
		parent::setUp();

		$this->admin = $this->factory()->user->create(
			[
				'role' => 'administrator',
			]
		);
	}

	/**
	 * Tests get_type_prefix();
	 */
	public function testGetTypePrefix(): void {
		$actual = CoreSchemaFilters::get_type_prefix();
		$this->assertEquals( '', $actual );

		$expected = '';
		$actual   = CoreSchemaFilters::get_type_prefix( $expected );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Tests check_if_secret_is_revoked() when the secret is revoked.
	 */
	public function testCheckIfSecretIsRevokedWhenRevoked(): void {
		$user_id = $this->factory()->user->create();

		$expected = 'test_token';

		User::set_is_secret_revoked( $user_id, true );

		$this->expectException( \GraphQL\Error\UserError::class );
		CoreSchemaFilters::check_if_secret_is_revoked( $expected, $user_id );
	}

	/**
	 * Tests check_if_secret_is_revoked()
	 */
	public function testCheckIfSecretIsRevoked(): void {
		$user_id = $this->factory()->user->create();

		$expected = 'test_token';

		TokenManager::refresh_user_secret( $user_id, false );

		$actual = CoreSchemaFilters::check_if_secret_is_revoked( $expected, $user_id );

		$this->assertEquals( $expected, $actual );
	}
}
