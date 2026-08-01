<?php
/**
 * Tests the TypeRegistry.
 *
 * @package Tests\WPGraphQL\Login\Integration
 */

namespace Tests\WPGraphQL\Login\Integration;

use Tests\WPGraphQL\Login\TestCase;
use WPGraphQL\Login\TypeRegistry;

/**
 * Tests TypeRegistry.
 */
class TypeRegistryTest extends TestCase {
	/**
	 * {@inheritDoc}
	 */
	public function setUp(): void {
		parent::setUp();
	}

	/**
	 * {@inheritDoc}
	 */
	public function tearDown(): void {
		parent::tearDown();
	}

	/**
	 * Tests TypeRegistry::get_registered_types()
	 */
	public function testGetRegisteredTypes() {
		$expected = TypeRegistry::get_registered_types();

		$this->assertNotEmpty( $expected );

		$this->reset_type_registry();

		$actual = TypeRegistry::get_registered_types();

		$this->assertNotEmpty( $actual );
	}

	/**
	 * Tests TypeRegistry::init()
	 */
	public function testInit() {
		$actual = \call_user_func( 'did_action', 'graphql_login_before_register_types' );
		$this->assertEquals( 0, $actual, 'Before action should not have been called yet' );

		$actual = \call_user_func( 'did_action', 'graphql_login_after_register_types' );
		$this->assertEquals( 0, $actual, 'After action should not have been called yet' );

		TypeRegistry::init();

		$actual = \call_user_func( 'did_action', 'graphql_login_before_register_types' );
		$this->assertEquals( 1, $actual, 'Before action should have been called once' );

		$actual = \call_user_func( 'did_action', 'graphql_login_after_register_types' );
		$this->assertEquals( 1, $actual, 'After action should have been called once' );
	}
}
