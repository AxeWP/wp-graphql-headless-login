<?php

use WPGraphQL\Login\Auth\User;
use WPGraphQL\Type\WPEnumType;

/**
 * Tests access functons
 */
class LoginClientTest extends \Tests\WPGraphQL\TestCase\WPGraphQLTestCase {
	public $tester;
	public $admin;
	public $test_user;

	/**
	 * {@inheritDoc}
	 */
	public function setUp(): void {
		parent::setUp();

		$this->tester->set_client_config(
			'facebook',
			[
				'name'      => 'Facebook',
				'slug'      => 'facebook',
				'order'     => 0,
				'isEnabled' => true,
			]
		);
		$this->tester->set_client_config(
			'google',
			[
				'name'      => 'Google',
				'slug'      => 'google',
				'order'     => 0,
				'isEnabled' => true,
			]
		);

		$this->admin     = $this->factory()->user->create(
			[
				'role' => 'administrator',
			]
		);
		$this->test_user = $this->factory()->user->create(
			[
				'role' => 'subscriber',
			]
		);

		// Add mock linked identities
		User::link_user_identity( $this->test_user, 'facebook', '1234567890' );
		User::link_user_identity( $this->test_user, 'google', '1234567890' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function tearDown(): void {
		$this->tester->clear_client_config( 'facebook' );
		$this->tester->clear_client_config( 'google' );
		$this->clearSchema();

		parent::tearDown();
	}

	/**
	 * Test the `user.auth` query.
	 */
	public function testUserAuthQuery(): void {
		$query = '
			query UserAuth( $id: ID! ) {
				user( id: $id, idType: DATABASE_ID ) {
					databaseId
					auth {
						authToken
						authTokenExpiration
						isUserSecretRevoked
						linkedIdentities {
							id
							provider
						}
						refreshToken
						refreshTokenExpiration
						userSecret
					}
				}
			}
		';

		$variables = [
			'id' => $this->test_user,
		];

		// Test as admin
		wp_set_current_user( $this->admin );
		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertQuerySuccessful(
			$actual,
			[
				$this->expectedObject(
					'user',
					[
						$this->expectedField( 'databaseId', $this->test_user ),
						$this->expectedObject(
							'auth',
							[
								$this->expectedField( 'authToken', self::IS_NULL ),
								$this->expectedField( 'authTokenExpiration', self::IS_NULL ),
								$this->expectedField( 'isUserSecretRevoked', false ),
								$this->expectedNode(
									'linkedIdentities',
									[
										$this->expectedField( 'id', '1234567890' ),
										$this->expectedField( 'provider', WPEnumType::get_safe_name( 'facebook' ) ),
									],
									0
								),
								$this->expectedNode(
									'linkedIdentities',
									[
										$this->expectedField( 'id', '1234567890' ),
										$this->expectedField( 'provider', WPEnumType::get_safe_name( 'google' ) ),
									],
									1
								),
								$this->expectedField( 'refreshToken', self::IS_NULL ),
								$this->expectedField( 'refreshTokenExpiration', self::IS_NULL ),
								$this->expectedField( 'userSecret', self::IS_NULL ),
							]
						),
					]
				),
			]
		);

		// Test as actual user
		wp_set_current_user( $this->test_user );
		$actual = $this->graphql( compact( 'query', 'variables' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertQuerySuccessful(
			$actual,
			[
				$this->expectedObject(
					'user',
					[
						$this->expectedField( 'databaseId', $this->test_user ),
						$this->expectedObject(
							'auth',
							[
								$this->expectedField( 'authToken', self::NOT_FALSY ),
								$this->expectedField( 'authTokenExpiration', self::NOT_FALSY ),
								$this->expectedField( 'isUserSecretRevoked', false ),
								$this->expectedNode(
									'linkedIdentities',
									[
										$this->expectedField( 'id', '1234567890' ),
										$this->expectedField( 'provider', WPEnumType::get_safe_name( 'facebook' ) ),
									],
									0
								),
								$this->expectedNode(
									'linkedIdentities',
									[
										$this->expectedField( 'id', '1234567890' ),
										$this->expectedField( 'provider', WPEnumType::get_safe_name( 'google' ) ),
									],
									1
								),
								$this->expectedField( 'refreshToken', self::NOT_FALSY ),
								$this->expectedField( 'refreshTokenExpiration', self::NOT_FALSY ),
								$this->expectedField( 'userSecret', self::NOT_FALSY ),
							]
						),
					]
				),
			]
		);
	}
}
