<?php
/**
 * Tests the ProviderEnum type.
 *
 * @package Tests\WPGraphQL\Login\Integration
 */

namespace Tests\WPGraphQL\Login\Integration;

use Tests\WPGraphQL\Login\TestCase;

/**
 * Tests ProviderEnum class
 */
class ProviderEnumTest extends TestCase {
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
	 * Tests ProviderEnum is set to none by default.
	 */
	public function testNoProviderEnum(): void {
		add_filter(
			'graphql_login_registered_provider_configs',
			static function () {
				return [];
			}
		);
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
