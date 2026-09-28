<?php
/**
 * A third-party provider config, registered with the `graphql_login_registered_provider_configs` filter.
 *
 * @package WPGraphQL\Login\Tests\Fixtures
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Tests\Fixtures;

use WPGraphQL\Login\Auth\ProviderConfig\ProviderConfig;

/**
 * A custom provider that never returns any user data.
 */
class FooCustomProviderConfig extends ProviderConfig {
	/**
	 * {@inheritDoc}
	 */
	public static function get_type(): string {
		return 'custom';
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_name(): string {
		return 'Foo Custom';
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_slug(): string {
		return 'foo-custom';
	}

	/**
	 * {@inheritDoc}
	 */
	public function authenticate_and_get_user_data( array $input ) {
		return false;
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_user_from_data( $data ) {
		return false;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function prepare_mutation_input( array $input ): array {
		return $input;
	}
}
