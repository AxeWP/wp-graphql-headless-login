<?php
/**
 * Tests authenticating with a third-party provider.
 *
 * @package WPGraphQL\Login\Tests\Integration\Auth
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Tests\Integration\Auth;

use PHPUnit\Framework\Attributes\CoversClass;
use WPGraphQL\Login\Auth\Auth;
use WPGraphQL\Login\Tests\Fixtures\FooCustomProviderConfig;
use WPGraphQL\Login\Tests\TestCase;

/**
 * Tests that providers added with the `graphql_login_registered_provider_configs` filter fail safely when they don't return user data.
 */
#[CoversClass( Auth::class )]
class CustomProviderTest extends TestCase {
	/**
	 * {@inheritDoc}
	 */
	protected function setUp(): void {
		parent::setUp();

		add_filter(
			'graphql_login_registered_provider_configs',
			static function ( array $providers ): array {
				$providers[ FooCustomProviderConfig::get_slug() ] = FooCustomProviderConfig::class;

				return $providers;
			}
		);

		$this->set_client_config( FooCustomProviderConfig::get_slug(), [ 'isEnabled' => true ] );
		$this->clearSchema();
	}

	/**
	 * {@inheritDoc}
	 */
	protected function tearDown(): void {
		$this->clear_client_config( FooCustomProviderConfig::get_slug() );
		$this->clearSchema();

		parent::tearDown();
	}

	/**
	 * Tests that logging in fails without user data from the provider.
	 */
	public function test_login_fails_without_user_data(): void {
		$query = '
			mutation Login( $input: LoginInput! ) {
				login( input: $input ) {
					authToken
				}
			}
		';

		$variables = [
			'input' => [
				'provider' => 'FOO_CUSTOM',
			],
		];

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertSame( 'The user could not be logged in.', $actual['errors'][0]['message'] );
	}

	/**
	 * Tests that linking an identity fails without user data from the provider.
	 */
	public function test_link_user_identity_fails_without_user_data(): void {
		$user_id = $this->factory()->user->create();
		wp_set_current_user( $user_id );

		$query = '
			mutation LinkUserIdentity( $input: LinkUserIdentityInput! ) {
				linkUserIdentity( input: $input ) {
					success
				}
			}
		';

		$variables = [
			'input' => [
				'provider' => 'FOO_CUSTOM',
				'userId'   => $user_id,
			],
		];

		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayHasKey( 'errors', $actual );
		$this->assertSame( 'Unable to get user data.', $actual['errors'][0]['message'] );
	}
}
