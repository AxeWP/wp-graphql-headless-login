<?php

use WPGraphQL\Login\Auth\ServerAuthentication;

/**
 * Tests ServerAuthentication.
 */
class ServerAuthenticationTest extends \Codeception\TestCase\WPTestCase {

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
	 * Tests determine_current_user.
	 *
	 * @covers \WPGraphQL\Login\Auth\ServerAuthentication
	 */
	public function testDetermineCurrentUser(): void {
		$instance = ServerAuthentication::instance();
		$user_id = $this->factory()->user->create();

		// Test without token.
		$actual = $instance->determine_current_user( $this->admin );

		$this->assertEquals( $this->admin, $actual );

		// Test with valid secret.
		$tokens = $this->tester->generate_user_tokens( $user_id );

		$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $tokens['auth_token'];

		$actual = $instance->determine_current_user( $this->admin );

		$this->assertEquals( $user_id, $actual );

		// Test user returns same user.
		$actual = $instance->determine_current_user( $user_id );

		$this->assertEquals( $user_id, $actual );

		// cleanup.
		unset( $_SERVER['HTTP_AUTHORIZATION'] );
		wp_delete_user( $user_id );
	}
}
