<?php
/**
 * Tests the WooCommerce extension.
 *
 * @package WPGraphQL\Login\Tests\Integration\Extensions
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Tests\Integration\Extensions;

use PHPUnit\Framework\Attributes\CoversClass;
use WPGraphQL\Login\Admin\Settings\ProviderSettings;
use WPGraphQL\Login\Extensions\WooCommerce;
use WPGraphQL\Login\Tests\TestCase;

/**
 * Tests the WPGraphQL for WooCommerce integration.
 */
#[CoversClass( WooCommerce::class )]
class WooCommerceTest extends TestCase {
	/**
	 * {@inheritDoc}
	 */
	protected function tearDown(): void {
		delete_option( ProviderSettings::$settings_prefix . 'password' );
		$this->reset_utils_properties();
		$this->clearSchema();

		parent::tearDown();
	}

	/**
	 * Tests that `Customer` is appended to the GraphQL user types that get the `auth` field.
	 */
	public function test_add_customer_to_user_types_appends_customer(): void {
		$this->assertSame( [ 'User', 'Customer' ], WooCommerce::add_customer_to_user_types( [ 'User' ] ) );
	}

	/**
	 * Tests that the `Customer` returned by the `login` mutation has the Headless Login `auth` field.
	 */
	public function test_login_payload_customer_has_auth_field(): void {
		if ( ! defined( 'WPGRAPHQL_WOOCOMMERCE_VERSION' ) || version_compare( WPGRAPHQL_WOOCOMMERCE_VERSION, '1.0.0', '<' ) ) {
			$this->markTestSkipped( 'Requires WPGraphQL for WooCommerce v1.0.0+ to be active.' );
		}

		$this->set_client_config(
			'password',
			[
				'name'          => 'Password',
				'slug'          => 'password',
				'order'         => 0,
				'isEnabled'     => true,
				'clientOptions' => [],
				'loginOptions'  => [
					'useAuthenticationCookie' => false,
				],
			]
		);
		$this->clearSchema();

		$user_id = $this->factory()->user->create(
			[
				'role'       => 'customer',
				'user_login' => 'testuser',
				'user_pass'  => 'testpass',
			]
		);

		$query = '
			mutation LoginWithPassword( $username: String!, $password: String! ) {
				login( input: { credentials: { username: $username, password: $password }, provider: PASSWORD } ) {
					user {
						auth {
							userSecret
						}
					}
					customer {
						databaseId
						auth {
							userSecret
						}
					}
				}
			}
		';

		$variables = [
			'username' => 'testuser',
			'password' => 'testpass',
		];

		$response = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayNotHasKey( 'errors', $response );
		$this->assertSame( $user_id, $response['data']['login']['customer']['databaseId'] );
		$this->assertNotEmpty( $response['data']['login']['customer']['auth']['userSecret'] );
		$this->assertSame( $response['data']['login']['user']['auth']['userSecret'], $response['data']['login']['customer']['auth']['userSecret'] );
	}
}
