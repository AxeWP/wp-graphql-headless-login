<?php
/**
 * Tests the TypeRegistry.
 *
 * @package Tests\WPGraphQL\Login\Integration\GraphQL
 */

namespace Tests\WPGraphQL\Login\Integration\GraphQL;

use Tests\WPGraphQL\Login\TestCase;
use WPGraphQL\Login\GraphQL\Type\Mutation\Login;
use WPGraphQL\Login\GraphQL\Type\WPObject\Client;
use WPGraphQL\Login\GraphQL\TypeRegistry;

/**
 * Tests TypeRegistry.
 */
class TypeRegistryTest extends TestCase {
	/**
	 * Tests TypeRegistry::init()
	 */
	public function testInit(): void {
		$this->reset_type_registry();

		$before_count = did_action( 'graphql_login_before_register_types' );
		$after_count  = did_action( 'graphql_login_after_register_types' );

		TypeRegistry::init();

		$this->assertSame( $before_count + 1, did_action( 'graphql_login_before_register_types' ), 'Before action should have been called once' );
		$this->assertSame( $after_count + 1, did_action( 'graphql_login_after_register_types' ), 'After action should have been called once' );

		$this->assertContains( Login::class, TypeRegistry::$registry );
		$this->assertContains( Client::class, TypeRegistry::$registry );
	}
}
