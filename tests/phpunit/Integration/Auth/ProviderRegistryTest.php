<?php
/**
 * Tests the provider registry.
 *
 * @package WPGraphQL\Login\Tests\Integration\Auth
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Tests\Integration\Auth;

use PHPUnit\Framework\Attributes\CoversClass;
use WPGraphQL\Login\Auth\ProviderConfig\OAuth2\Facebook;
use WPGraphQL\Login\Auth\ProviderConfig\Password;
use WPGraphQL\Login\Auth\ProviderRegistry;
use WPGraphQL\Login\Tests\TestCase;
use WPGraphQL\Utils\DebugLog;

/**
 * Tests the Auth\ProviderRegistry class.
 */
#[CoversClass( ProviderRegistry::class )]
class ProviderRegistryTest extends TestCase {
	/**
	 * {@inheritDoc}
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->reset_provider_registry();
	}

	/**
	 * {@inheritDoc}
	 */
	protected function tearDown(): void {
		$this->clear_client_config( 'facebook' );
		$this->clear_client_config( 'password' );

		parent::tearDown();
	}

	/**
	 * Tests that only the enabled providers are instantiated.
	 */
	public function test_only_enabled_providers_are_instantiated(): void {
		$this->assertSame( [], ProviderRegistry::get_instance()->get_providers() );

		$this->set_client_config(
			'facebook',
			[
				'isEnabled'     => true,
				'clientOptions' => [
					'clientId'        => 'mock_client_id',
					'clientSecret'    => 'mock_client_secret',
					'graphAPIVersion' => 'v16.0',
				],
			]
		);
		$this->set_client_config( 'password', [ 'isEnabled' => true ] );

		$registry  = ProviderRegistry::get_instance();
		$providers = $registry->get_providers();

		$this->assertSame( [ 'facebook', 'password' ], array_keys( $providers ) );
		$this->assertInstanceOf( Facebook::class, $registry->get_provider_config( 'facebook' ) );
		$this->assertInstanceOf( Password::class, $registry->get_provider_config( 'password' ) );
		$this->assertSame( $registry, ProviderRegistry::get_instance(), 'The registry should be memoized.' );
	}

	/**
	 * Tests that getting the config for a provider that isn't enabled throws an exception.
	 */
	public function test_get_provider_config_throws_for_disabled_provider(): void {
		$this->expectException( \Throwable::class );
		$this->expectExceptionMessage( 'Provider facebook is not enabled.' );

		ProviderRegistry::get_instance()->get_provider_config( 'facebook' );
	}

	/**
	 * Tests that filtered provider classes are sorted by slug, and invalid ones are skipped with a debug message.
	 */
	public function test_registered_providers_can_be_filtered(): void {
		$this->set_client_config( 'password', [ 'isEnabled' => true ] );

		add_filter(
			'graphql_login_registered_provider_configs',
			static function ( array $providers ): array {
				unset( $providers['facebook'] );

				$providers['aaa-missing']   = 'WPGraphQL\Login\Tests\MissingProviderConfig';
				$providers['zzz-not-valid'] = \stdClass::class;

				return $providers;
			}
		);

		$debug_log = new DebugLog();
		$registry  = ProviderRegistry::get_instance();

		$registered = array_keys( $registry->get_registered_providers() );

		$sorted = $registered;
		sort( $sorted );

		$this->assertSame( $sorted, $registered, 'The providers should be sorted by slug.' );
		$this->assertContains( 'aaa-missing', $registered );
		$this->assertContains( 'zzz-not-valid', $registered );
		$this->assertNotContains( 'facebook', $registered );

		// Only the valid, enabled provider is instantiated.
		$this->assertSame( [ 'password' ], array_keys( $registry->get_providers() ) );

		$this->assertSame(
			[
				'The WPGraphQL\Login\Tests\MissingProviderConfig ProviderConfig class does not exist.',
				'Class stdClass must extend WPGraphQL\Login\Auth\ProviderConfig\ProviderConfig.',
			],
			wp_list_pluck( $debug_log->get_logs(), 'message' )
		);
	}
}
