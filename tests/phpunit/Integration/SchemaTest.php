<?php
/**
 * Tests the GraphQL schema.
 *
 * @package Tests\WPGraphQL\Login\Integration
 */

namespace Tests\WPGraphQL\Login\Integration;

use Tests\WPGraphQL\Login\TestCase;

/**
 * Ensures the schema is valid.
 */
class SchemaTest extends TestCase {
	/**
	 * Test the schema can be generated and is valid.
	 */
	public function testSchema() {
		try {
			new \WPGraphQL\Request();

			$schema = \WPGraphQL::get_schema();
			$this->clearSchema();
			$schema->assertValid();

			$this->assertTrue( true );
		} catch ( \GraphQL\Error\InvariantViolation ) {
			$this->clearSchema();
			$this->assertTrue( false );
		}
	}
}
