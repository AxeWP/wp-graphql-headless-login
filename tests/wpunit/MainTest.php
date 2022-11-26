<?php

use WPGraphQL\Login\Main;

/**
 * Tests Main.
 */
class MainTest extends \Codeception\TestCase\WPTestCase {

	public $instance;

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
		$this->assertTrue( $this->instance instanceof Main );
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
	 * @covers \WPGraphQL\Login\Main::__wakeup
	 * @covers \WPGraphQL\Login\Main::__clone
	 */
	public function testClone() {
		$actual = Main::instance();
		$rc     = new ReflectionClass( $actual );
		$this->assertTrue( $rc->hasMethod( '__clone' ) );
		$this->assertTrue( $rc->hasMethod( '__wakeup' ) );
	}

	/**
	 * Tests the `init` action works
	 *
	 * @covers \WPGraphQL\Login\Main
	 */
	public function testConstants() {
		do_action( 'init' );
		$this->assertTrue( defined( 'WPGRAPHQL_LOGIN_VERSION' ) );
		$this->assertTrue( defined( 'WPGRAPHQL_LOGIN_PLUGIN_DIR' ) );
		$this->assertTrue( defined( 'WPGRAPHQL_LOGIN_PLUGIN_URL' ) );
		$this->assertTrue( defined( 'WPGRAPHQL_LOGIN_PLUGIN_FILE' ) );
	}

}
