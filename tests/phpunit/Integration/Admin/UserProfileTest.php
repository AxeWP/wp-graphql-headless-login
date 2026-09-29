<?php
/**
 * Tests the user profile screen additions.
 *
 * @package WPGraphQL\Login\Tests\Integration\Admin
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Tests\Integration\Admin;

use PHPUnit\Framework\Attributes\CoversClass;
use WPAjaxDieStopException;
use WPGraphQL\Login\Admin\UserProfile;
use WPGraphQL\Login\Auth\User;
use WPGraphQL\Login\Tests\TestCase;

/**
 * Tests the Admin\UserProfile class.
 */
#[CoversClass( UserProfile::class )]
#[CoversClass( User::class )]
class UserProfileTest extends TestCase {
	/**
	 * The ID of the user whose profile is being edited.
	 */
	private int $user_id;

	/**
	 * The ID of an administrator.
	 */
	private int $admin_id;

	/**
	 * {@inheritDoc}
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->user_id  = $this->factory()->user->create( [ 'role' => 'subscriber' ] );
		$this->admin_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );

		$this->set_client_config(
			'facebook',
			[
				'name'          => 'Facebook',
				'isEnabled'     => true,
				'clientOptions' => [
					'clientId'        => 'mock_client_id',
					'clientSecret'    => 'mock_client_secret',
					'redirectUri'     => 'https://example.com/callback',
					'graphAPIVersion' => 'v16.0',
				],
			]
		);
		$this->set_client_config(
			'github',
			[
				'name'          => 'GitHub',
				'isEnabled'     => true,
				'clientOptions' => [
					'clientId'     => 'mock_client_id',
					'clientSecret' => 'mock_client_secret',
					'redirectUri'  => 'https://example.com/callback',
				],
			]
		);
		$this->set_client_config( 'password', [ 'isEnabled' => true ] );

		User::link_user_identity( $this->user_id, 'facebook', 'fb-identity-123' );

		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter( 'wp_die_ajax_handler', [ $this, 'get_ajax_die_handler' ] );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function tearDown(): void {
		$_POST    = [];
		$_REQUEST = [];

		$this->clear_client_config( 'facebook' );
		$this->clear_client_config( 'github' );
		$this->clear_client_config( 'password' );

		parent::tearDown();
	}

	/**
	 * Returns a wp_die() handler that stops execution with an exception, so the ajax response can be inspected.
	 */
	public function get_ajax_die_handler(): callable {
		return static function ( $message ): void {
			throw new WPAjaxDieStopException( (string) $message );
		};
	}

	/**
	 * Tests that the profile screen lists each enabled OAuth provider with its linked identity, and only lets the user unlink their own identities.
	 */
	public function test_profile_lists_linked_identities(): void {
		wp_set_current_user( $this->user_id );

		$output = $this->render_profile_fields( $this->user_id );

		// The user secret can be revoked.
		$this->assertStringContainsString( 'id="revoke-user-secret-key"', $output );
		$this->assertStringContainsString( 'data-user-id="' . $this->user_id . '"', $output );

		// The linked identity is shown with an unlink button.
		$this->assertMatchesRegularExpression( '/id="' . User::get_identity_meta_key( 'facebook' ) . '"[^>]*value="fb-identity-123"/', $output );
		$this->assertStringContainsString( 'id="unlink-user-identity-facebook"', $output );

		// The unlinked identity has no button.
		$this->assertMatchesRegularExpression( '/id="' . User::get_identity_meta_key( 'github' ) . '"[^>]*value="Not linked"/', $output );
		$this->assertStringNotContainsString( 'id="unlink-user-identity-github"', $output );

		// The password provider has no identity to link.
		$this->assertStringNotContainsString( User::get_identity_meta_key( 'password' ), $output );
	}

	/**
	 * Tests that other users can see a user's linked identities, but not unlink them.
	 */
	public function test_profile_hides_unlink_button_from_other_users(): void {
		wp_set_current_user( $this->admin_id );

		$output = $this->render_profile_fields( $this->user_id );

		$this->assertMatchesRegularExpression( '/id="' . User::get_identity_meta_key( 'facebook' ) . '"[^>]*value="fb-identity-123"/', $output );
		$this->assertStringNotContainsString( 'unlink-user-identity-', $output );
	}

	/**
	 * Tests that a user can unlink their own identity.
	 */
	public function test_unlink_identity_removes_the_users_identity(): void {
		wp_set_current_user( $this->user_id );

		$actual = $this->do_ajax(
			'graphql_login_unlink_identity',
			[
				'nonce'    => wp_create_nonce( 'wp-graphql-headless-login-unlink-identity' ),
				'user_id'  => $this->user_id,
				'provider' => 'facebook',
			]
		);

		$this->assertSame( [ 'success' => true ], $actual );
		$this->assertSame( [], User::get_user_identities( $this->user_id ) );
	}

	/**
	 * Tests that users with `edit_users` can unlink another user's identity.
	 */
	public function test_unlink_identity_allows_user_editors(): void {
		wp_set_current_user( $this->admin_id );

		$actual = $this->do_ajax(
			'graphql_login_unlink_identity',
			[
				'nonce'    => wp_create_nonce( 'wp-graphql-headless-login-unlink-identity' ),
				'user_id'  => $this->user_id,
				'provider' => 'facebook',
			]
		);

		$this->assertSame( [ 'success' => true ], $actual );
		$this->assertSame( [], User::get_user_identities( $this->user_id ) );
	}

	/**
	 * Tests that unlinking an identity fails for bad requests, leaving the identity linked.
	 */
	public function test_unlink_identity_rejects_invalid_requests(): void {
		wp_set_current_user( $this->user_id );
		$nonce = wp_create_nonce( 'wp-graphql-headless-login-unlink-identity' );

		// Missing user ID.
		$actual = $this->do_ajax(
			'graphql_login_unlink_identity',
			[
				'nonce'    => $nonce,
				'provider' => 'facebook',
			]
		);
		$this->assertSame(
			[
				'success' => false,
				'data'    => 'Invalid user ID.',
			],
			$actual
		);

		// Missing provider.
		$actual = $this->do_ajax(
			'graphql_login_unlink_identity',
			[
				'nonce'   => $nonce,
				'user_id' => $this->user_id,
			]
		);
		$this->assertSame(
			[
				'success' => false,
				'data'    => 'Invalid provider.',
			],
			$actual
		);

		// Provider with no linked identity.
		$actual = $this->do_ajax(
			'graphql_login_unlink_identity',
			[
				'nonce'    => $nonce,
				'user_id'  => $this->user_id,
				'provider' => 'github',
			]
		);
		$this->assertSame(
			[
				'success' => false,
				'data'    => 'There was an error unlinking the identity.',
			],
			$actual
		);

		// Another user's identity.
		$other_user = $this->factory()->user->create( [ 'role' => 'subscriber' ] );
		User::link_user_identity( $other_user, 'facebook', 'fb-identity-456' );

		$actual = $this->do_ajax(
			'graphql_login_unlink_identity',
			[
				'nonce'    => $nonce,
				'user_id'  => $other_user,
				'provider' => 'facebook',
			]
		);
		$this->assertSame(
			[
				'success' => false,
				'data'    => 'You do not have permission to unlink identities for this user.',
			],
			$actual
		);
		$this->assertSame( [ 'facebook' => 'fb-identity-456' ], User::get_user_identities( $other_user ) );

		// Bad nonce.
		$actual = $this->do_ajax(
			'graphql_login_unlink_identity',
			[
				'nonce'    => 'bad-nonce',
				'user_id'  => $this->user_id,
				'provider' => 'facebook',
			]
		);
		$this->assertSame( -1, $actual );

		$this->assertSame( [ 'facebook' => 'fb-identity-123' ], User::get_user_identities( $this->user_id ) );
	}

	/**
	 * Tests that a user can revoke their own secret, invalidating the previous one.
	 */
	public function test_revoke_secret_replaces_the_users_secret(): void {
		wp_set_current_user( $this->user_id );
		User::set_secret( $this->user_id, 'old-secret' );

		$actual = $this->do_ajax(
			'graphql_login_revoke_user_secret_key',
			[
				'nonce'   => wp_create_nonce( 'wp-graphql-headless-login-revoke-user-secret-key' ),
				'user_id' => $this->user_id,
			]
		);

		$this->assertSame( [ 'success' => true ], $actual );

		$secret = User::get_secret( $this->user_id );
		$this->assertNotEmpty( $secret );
		$this->assertNotSame( 'old-secret', $secret );
	}

	/**
	 * Tests that revoking a secret fails for bad requests, leaving the secret untouched.
	 */
	public function test_revoke_secret_rejects_invalid_requests(): void {
		wp_set_current_user( $this->admin_id );
		User::set_secret( $this->user_id, 'old-secret' );
		$nonce = wp_create_nonce( 'wp-graphql-headless-login-revoke-user-secret-key' );

		// Missing user ID.
		$actual = $this->do_ajax( 'graphql_login_revoke_user_secret_key', [ 'nonce' => $nonce ] );
		$this->assertSame(
			[
				'success' => false,
				'data'    => 'Invalid user ID.',
			],
			$actual
		);

		// Another user's secret.
		$actual = $this->do_ajax(
			'graphql_login_revoke_user_secret_key',
			[
				'nonce'   => $nonce,
				'user_id' => $this->user_id,
			]
		);
		$this->assertSame(
			[
				'success' => false,
				'data'    => 'There was an error revoking the user secret key: The Secret cannot be refreshed for this user..',
			],
			$actual
		);

		// Bad nonce.
		wp_set_current_user( $this->user_id );

		$actual = $this->do_ajax(
			'graphql_login_revoke_user_secret_key',
			[
				'nonce'   => 'bad-nonce',
				'user_id' => $this->user_id,
			]
		);
		$this->assertSame( -1, $actual );

		$this->assertSame( 'old-secret', User::get_secret( $this->user_id ) );
	}

	/**
	 * Renders the plugin's fields on the user profile screen.
	 *
	 * @param int $user_id The ID of the user whose profile is being edited.
	 */
	private function render_profile_fields( int $user_id ): string {
		ob_start();
		do_action( 'edit_user_profile', get_user_by( 'id', $user_id ) );

		return (string) ob_get_clean();
	}

	/**
	 * Runs an ajax action, returning the decoded JSON response.
	 *
	 * @param string              $action The ajax action.
	 * @param array<string,mixed> $data   The POST data.
	 *
	 * @return mixed The decoded response, or the wp_die() message if no JSON was sent.
	 */
	private function do_ajax( string $action, array $data ) {
		$_POST    = $data;
		$_REQUEST = $data;

		ob_start();
		try {
			do_action( 'wp_ajax_' . $action );
			$message = null;
		} catch ( WPAjaxDieStopException $e ) {
			$message = $e->getMessage();
		}
		$output = (string) ob_get_clean();

		return '' !== $output ? json_decode( $output, true ) : (int) $message;
	}
}
