<?php

use WPGraphQL\Login\Main;

/**
 * Tests Main.
 */
class MainTest extends \lucatume\WPBrowser\TestCase\WPTestCase {
	public $instance;

	/**
	 * {@inheritDoc}
	 */
	public function tearDown(): void {
		unset( $this->instance );

		parent::tearDown();
	}

	/**
	 * Tests instance.
	 *
	 * @covers \WPGraphQL\Login\Main
	 */
	public function testInstance() {
		$this->instance = new Main();
		$this->assertInstanceOf( Main::class, $this->instance );
	}

	/**
	 * Tests instance before instantiation.
	 *
	 * @covers \WPGraphQL\Login\Main
	 */
	public function testInstanceBeforeInstantiation() {
		$instance = Main::instance();
		$this->assertTrue( $instance instanceof Main );
	}

	/**
	 * Test cloning does not work.
	 *
	 * @covers \WPGraphQL\Login\Main
	 */
	public function testClone(): void {
		$this->setExpectedIncorrectUsage( '__clone' );

		$instance = Main::instance();
		clone $instance;
	}

	/**
	 * Test deserializing does not work.
	 */
	public function testWakeup(): void {
		$this->setExpectedIncorrectUsage( '__wakeup' );

		$instance            = Main::instance();
		$serialized_instance = serialize( $instance );

		unserialize( $serialized_instance );
	}

	/**
	 * Tests the `init` action works
	 *
	 * @covers \WPGraphQL\Login\Main
	 */
	public function testConstants() {
		$this->assertTrue( defined( 'WPGRAPHQL_LOGIN_VERSION' ) );
		$this->assertTrue( defined( 'WPGRAPHQL_LOGIN_PLUGIN_DIR' ) );
		$this->assertTrue( defined( 'WPGRAPHQL_LOGIN_PLUGIN_URL' ) );
		$this->assertTrue( defined( 'WPGRAPHQL_LOGIN_PLUGIN_FILE' ) );
	}
}
