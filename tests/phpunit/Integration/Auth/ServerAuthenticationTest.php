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
	 * The `iss` values of the tokens minted during the test.
	 */
	private array $token_issuers = [];

	/**
	 * Returns the `iss` claim from the given token.
	 */
	private function getTokenIssuer( string $token ): ?string {
		$parts = explode( '.', $token );

		if ( 3 !== count( $parts ) ) {
			return null;
		}

		$payload = json_decode( (string) base64_decode( strtr( $parts[1], '-_', '+/' ) ) );

		return isset( $payload->iss ) && is_string( $payload->iss ) ? $payload->iss : null;
	}

	/**
	 * Adds the test site URLs and minted token issuers to the allowed `iss` domains.
	 */
	public function filterAllowedIssDomains( array $allowed_domains ): array {
		$home_url = (string) call_user_func( 'home_url' );

		return array_values(
			array_filter(
				array_unique(
					array_merge(
						$allowed_domains,
						[
							$home_url,
							str_replace( 'http://', 'https://', $home_url ),
							str_replace( 'https://', 'http://', $home_url ),
							...$this->token_issuers,
						]
					)
				)
			)
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function setUp(): void {
		parent::setUp();

		add_filter( 'graphql_login_iss_allowed_domains', [ $this, 'filterAllowedIssDomains' ] );

		$this->admin = $this->factory()->user->create(
			[
				'role' => 'administrator',
			]
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function tearDown(): void {
		call_user_func( 'remove_filter', 'graphql_login_iss_allowed_domains', [ $this, 'filterAllowedIssDomains' ] );

		parent::tearDown();
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
		$issuer = $this->getTokenIssuer( $tokens['auth_token'] );

		if ( null !== $issuer ) {
			$this->token_issuers[] = $issuer;
		}

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
