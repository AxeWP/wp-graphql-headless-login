<?php
/**
 * Tests the Auth\ServerAuthentication class.
 *
 * @package Tests\WPGraphQL\Login\Integration\Auth
 */

namespace Tests\WPGraphQL\Login\Integration\Auth;

use PHPUnit\Framework\Attributes\CoversClass;
use Tests\WPGraphQL\Login\TestCase;
use WPGraphQL\Login\Auth\ServerAuthentication;

/**
 * Tests ServerAuthentication.
 */
#[CoversClass( ServerAuthentication::class )]
class ServerAuthenticationTest extends TestCase {
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
	 * Tests determine_current_user.
	 */
	public function testDetermineCurrentUser(): void {
		$instance = ServerAuthentication::instance();
		$user_id  = $this->factory()->user->create();

		// Test without token.
		$actual = $instance->determine_current_user( $this->admin );

		$this->assertEquals( $this->admin, $actual );

		// Test with valid secret.
		$tokens = $this->generate_user_tokens( $user_id );

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
