<?php
/**
 * Tests the TypeRegistry.
 *
 * @package WPGraphQL\Login\Tests\Integration\GraphQL
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Tests\Integration\GraphQL;

use PHPUnit\Framework\Attributes\CoversClass;
use WPGraphQL;
use WPGraphQL\Login\GraphQL\TypeRegistry;
use WPGraphQL\Login\Tests\TestCase;
use WPGraphQL\Request;

/**
 * Tests TypeRegistry.
 */
#[CoversClass( TypeRegistry::class )]
class TypeRegistryTest extends TestCase {
	/**
	 * Tests that init() fires the before and after type registration actions.
	 */
	public function test_init_fires_before_and_after_register_types_actions(): void {
		$this->reset_type_registry();

		$before_count = did_action( 'graphql_login_before_register_types' );
		$after_count  = did_action( 'graphql_login_after_register_types' );

		TypeRegistry::init();

		$this->assertSame( $before_count + 1, did_action( 'graphql_login_before_register_types' ), 'Before action should have been called once' );
		$this->assertSame( $after_count + 1, did_action( 'graphql_login_after_register_types' ), 'After action should have been called once' );
	}

	/**
	 * Tests that the registered enums, inputs, interfaces, objects, fields and mutations are added to the schema.
	 */
	public function test_registered_types_are_added_to_the_schema(): void {
		$this->clearSchema();

		$query = '
			query {
				enumType: __type(name: "LoginProviderEnum") {
					kind
				}
				inputType: __type(name: "OAuthProviderResponseInput") {
					kind
				}
				interfaceType: __type(name: "LoginClientOptions") {
					kind
				}
				objectType: __type(name: "LoginClient") {
					kind
				}
				rootQuery: __type(name: "RootQuery") {
					fields {
						name
					}
				}
				rootMutation: __type(name: "RootMutation") {
					fields {
						name
					}
				}
			}
		';

		$actual = $this->graphql( compact( 'query' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertSame( 'ENUM', $actual['data']['enumType']['kind'] );
		$this->assertSame( 'INPUT_OBJECT', $actual['data']['inputType']['kind'] );
		$this->assertSame( 'INTERFACE', $actual['data']['interfaceType']['kind'] );
		$this->assertSame( 'OBJECT', $actual['data']['objectType']['kind'] );
		$this->assertContains( 'loginClients', wp_list_pluck( $actual['data']['rootQuery']['fields'], 'name' ) );
		$this->assertContains( 'login', wp_list_pluck( $actual['data']['rootMutation']['fields'], 'name' ) );
	}

	/**
	 * Tests that the schema with the registered types can be generated and is valid.
	 */
	public function test_registered_types_produce_a_valid_schema(): void {
		new Request();

		$schema = WPGraphQL::get_schema();
		$this->clearSchema();

		// Throws an InvariantViolation describing the first problem found.
		$schema->assertValid();
		$this->addToAssertionCount( 1 );
	}

	/**
	 * Tests that classes which aren't GraphQL types are rejected.
	 */
	public function test_init_rejects_classes_that_are_not_graphql_types(): void {
		$this->reset_type_registry();

		add_filter(
			'graphql_login_registered_object_classes',
			static fn ( array $classes ): array => array_merge( $classes, [ \stdClass::class ] )
		);

		$this->expectException( \Throwable::class );
		$this->expectExceptionMessage( 'To be registered to the WPGraphQL schema, stdClass needs to implement' );

		TypeRegistry::init();
	}

	/**
	 * Tests that no types are registered when they're all filtered out.
	 */
	public function test_init_registers_nothing_when_all_types_are_filtered_out(): void {
		$this->reset_type_registry();

		foreach ( [ 'enum', 'input', 'interface', 'object', 'connection', 'mutation', 'field' ] as $kind ) {
			add_filter( 'graphql_login_registered_' . $kind . '_classes', '__return_empty_array' );
		}

		TypeRegistry::init();

		$this->assertSame( [], TypeRegistry::$registry );
	}
}
