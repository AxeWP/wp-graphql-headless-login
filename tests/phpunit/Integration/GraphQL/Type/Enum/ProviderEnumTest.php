<?php
/**
 * Tests the ProviderEnum type.
 *
 * @package WPGraphQL\Login\Tests\Integration\GraphQL\Type\Enum
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Tests\Integration\GraphQL\Type\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use WPGraphQL\Login\GraphQL\Type\Enum\ProviderEnum;
use WPGraphQL\Login\Tests\TestCase;

/**
 * Tests ProviderEnum class
 */
#[CoversClass( ProviderEnum::class )]
class ProviderEnumTest extends TestCase {
	/**
	 * {@inheritDoc}
	 */
	protected function tearDown(): void {
		$this->reset_provider_registry();
		$this->clearSchema();

		parent::tearDown();
	}

	/**
	 * Tests that the enum only has a NONE value when no providers are registered.
	 */
	public function test_enum_falls_back_to_none_when_no_providers_are_registered(): void {
		add_filter( 'graphql_login_registered_provider_configs', '__return_empty_array' );
		$this->reset_provider_registry();
		$this->clearSchema();

		// Introspect LoginProviderEnum type and possible values.
		$query = '
			query {
				__type(name: "LoginProviderEnum") {
					name
					kind
					enumValues {
						name
					}
				}
			}
		';

		$actual = $this->graphql( compact( 'query' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );
		$this->assertCount( 1, $actual['data']['__type']['enumValues'] );
		$this->assertEquals( 'NONE', $actual['data']['__type']['enumValues'][0]['name'] );
	}
}
