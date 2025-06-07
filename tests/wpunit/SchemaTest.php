<?php


/**
 * Ensures the schema is valid.
 */
class SchemaTest extends \Tests\WPGraphQL\TestCase\WPGraphQLTestCase {
	/**
	 * Test the schema can be generated and is valid.
	 */
	public function testSchema() {
		try {
			$request = new \WPGraphQL\Request();

			$schema = WPGraphQL::get_schema();
			$this->clearSchema();
			$schema->assertValid();

			// Assert true upon success.
			$this->assertTrue( true );
		} catch ( \GraphQL\Error\InvariantViolation $e ) {
			// use --debug flag to view.
			codecept_debug( $e->getMessage() );
			$this->clearSchema();
			// Fail upon throwing
			$this->assertTrue( false );
		}
	}
}
