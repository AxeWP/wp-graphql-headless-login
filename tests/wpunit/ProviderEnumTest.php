<?php

use WPGraphQL\Login\Admin\Settings;

/**
 * Tests ProviderEnum class
 */
class ProviderEnumTest extends \Tests\WPGraphQL\TestCase\WPGraphQLTestCase {
	/**
	 * @var \WpunitTester
	 */
	public $tester;

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
	 *
	 * @covers \WPGraphQL\Login\Type\Enum\ProviderEnum::get_values()
	 */
	public function testNoProviderEnum() : void {
		add_filter( 'graphql_login_registered_provider_configs', function() {
			return [];
		});
		$this->tester->reset_provider_registry();
		/// Introspect LoginProviderEnum type and possible values.
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
