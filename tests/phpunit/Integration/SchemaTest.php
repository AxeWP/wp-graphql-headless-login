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
	public function testSchema(): void {
		new \WPGraphQL\Request();

		$schema = \WPGraphQL::get_schema();
		$this->clearSchema();

		// Throws an InvariantViolation describing the first problem found.
		$schema->assertValid();
		$this->addToAssertionCount( 1 );
	}
}
