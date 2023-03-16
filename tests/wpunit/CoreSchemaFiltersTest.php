<?php

use WPGraphQL\Login\Auth\TokenManager;
use WPGraphQL\Login\Auth\User;
use WPGraphQL\Login\CoreSchemaFilters;

/**
 * Tests CoreSchemaFilters.
 */
class CoreSchemaFiltersTest extends \Codeception\TestCase\WPTestCase {

	/**
	 * @var \WpunitTester
	 */
	protected $tester;
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
	public function testGetTypePrefix() : void {
		$actual = CoreSchemaFilters::get_type_prefix();
		$this->assertEquals( '', $actual );

		$expected = '';
		$actual   = CoreSchemaFilters::get_type_prefix( $expected );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Tests check_if_secret_is_revoked() when no secret.
	 *
	 * @covers \WPGraphQL\Login\CoreSchemaFilters
	 */
	public function testCheckIfSecretIsRevokedWhenRevoked() : void {
		$user_id = $this->factory()->user->create();

		// We should get a thrown UserError.
		$expected = 'test_token';

		// Set the user meta as true, marking the secret as revoked.
		User::set_is_secret_revoked( $user_id, true );

		$this->expectException( \GraphQL\Error\UserError::class );
		$actual = CoreSchemaFilters::check_if_secret_is_revoked( $expected, $user_id );
	}

	/**
	 * Tests check_if_secret_is_revoked()
	 *
	 * @covers \WPGraphQL\Login\CoreSchemaFilters
	 */
	public function testCheckIfSecretIsRevoked() : void {
		$user_id = $this->factory()->user->create();

		$expected = 'test_token';

		// Test with valid secret
		$secret = TokenManager::refresh_user_secret( $user_id, false );

		$actual = CoreSchemaFilters::check_if_secret_is_revoked( $expected, $user_id );

		$this->assertEquals( $expected, $actual );
	}

	public function testDetermineCurrentUser(): void {
		$user_id = $this->factory()->user->create();

		// Test without token.
		$actual = CoreSchemaFilters::determine_current_user( $this->admin );

		$this->assertEquals( $this->admin, $actual );

		// Test with valid secret.
		$tokens = $this->tester->generate_user_tokens( $user_id );

		$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $tokens['auth_token'];

		$actual = CoreSchemaFilters::determine_current_user( $this->admin );

		$this->assertEquals( $user_id, $actual );

		// Test user returns same user.
		$actual = CoreSchemaFilters::determine_current_user( $user_id );

		$this->assertEquals( $user_id, $actual );

		// cleanup.
		unset( $_SERVER['HTTP_AUTHORIZATION'] );
		wp_delete_user( $user_id );
	}
}
