<?php

use WPGraphQL\Login\TypeRegistry;

/**
 * Tests TypeRegistry.
 */
class TypeRegistryTest extends \lucatume\WPBrowser\TestCase\WPTestCase {
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
	 *
	 * @covers \WPGraphQL\Login\TypeRegistry
	 */
	public function testGetRegisteredTypes() {
		// Test it returns an array.
		$expected = TypeRegistry::get_registered_types();

		$this->assertNotEmpty( $expected );

		// Clear the registry.
		$this->tester->reset_type_registry();

		// Test it regenerates.
		$actual = TypeRegistry::get_registered_types();

		$this->assertNotEmpty( $actual );
	}

	/**
	 * Tests TypeRegistry::init()
	 *
	 * @covers \WPGraphQL\Login\TypeRegistry
	 */
	public function testInit() {

		// Test the before action.
		$actual = did_action( 'graphql_login_before_register_types' );
		$this->assertEquals( 0, $actual, 'Before action should not have been called yet' );

		// Test the after action.
		$actual = did_action( 'graphql_login_after_register_types' );
		$this->assertEquals( 0, $actual, 'After action should not have been called yet' );

		// Test init.
		TypeRegistry::init();

		// Test the before action.
		$actual = did_action( 'graphql_login_before_register_types' );
		$this->assertEquals( 1, $actual, 'Before action should have been called once' );

		// Test the after action.
		$actual = did_action( 'graphql_login_after_register_types' );
		$this->assertEquals( 1, $actual, 'After action should have been called once' );
	}
}
