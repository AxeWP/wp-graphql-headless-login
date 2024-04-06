<?php
/**
 * The registry of supported Authenticated providers.
 *
 * @package WPGraphQL\Login\Auth
 * @since 0.0.1
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Auth;

use WPGraphQL\Login\Auth\ProviderConfig\OAuth2\Facebook;
use WPGraphQL\Login\Auth\ProviderConfig\OAuth2\Generic;
use WPGraphQL\Login\Auth\ProviderConfig\OAuth2\GitHub;
use WPGraphQL\Login\Auth\ProviderConfig\OAuth2\Google;
use WPGraphQL\Login\Auth\ProviderConfig\OAuth2\Instagram;
use WPGraphQL\Login\Auth\ProviderConfig\OAuth2\LinkedIn;
use WPGraphQL\Login\Auth\ProviderConfig\Password;
use WPGraphQL\Login\Auth\ProviderConfig\ProviderConfig;
use WPGraphQL\Login\Auth\ProviderConfig\SiteToken;

/**
 * Class - ProviderRegistry
 */
class ProviderRegistry {
	/**
	 * The one true ProviderRegistry
	 *
	 * @var ?\WPGraphQL\Login\Auth\ProviderRegistry
	 */
	private static $instance;

	/**
	 * The registered provider classes, keyed to their slug.
	 *
	 * @var array<string,class-string<\WPGraphQL\Login\Auth\ProviderConfig\ProviderConfig>>
	 */
	private array $registered_providers = [];

	/**
	 * The enabled Authentication Providers.
	 *
	 * @var array<string,\WPGraphQL\Login\Auth\ProviderConfig\ProviderConfig>
	 */
	private array $providers = [];

	/**
	 * The constructor.
	 */
	public function __construct() {
		$this->registered_providers = $this->get_registered_providers();

		// Validate the providers, and then add them to the registry.
		foreach ( $this->registered_providers as $slug => $class ) {
			/** @var string $class */

			// Skip if the provider class does not exist.
			if ( ! class_exists( $class ) ) {
				graphql_debug(
					sprintf(
						// translators: %s is the provider config class name.
						__( 'The %s ProviderConfig class does not exist.', 'wp-graphql-headless-login' ),
						$class
					),
					[
						'provider' => $slug,
					]
				);
				continue;
			}

			// Skip if the provider class does not extend ProviderConfig.
			if ( ! is_subclass_of( $class, ProviderConfig::class, true ) ) {
				graphql_debug(
					sprintf(
					// translators: %1s is the class name for the provider. %2s is the abstract ProviderConfig class.
						__( 'Class %1$s must extend %2$s.', 'wp-graphql-headless-login' ),
						$class,
						ProviderConfig::class
					)
				);
				continue;
			}

			/**
			 * Skip if the provider is disabled.
			 *
			 * @var \WPGraphQL\Login\Auth\ProviderConfig\ProviderConfig $class
			 */
			if ( ! $class::is_enabled() ) {
				continue;
			}

			// Store the instantiated provider config.
			$class                                 = new $class();
			$this->providers[ $class->get_slug() ] = $class;

			/**
			 * Filters the provider instances.
			 *
			 * @param array $providers The instantiated provider config instances.
			 */
			$this->providers = apply_filters( 'graphql_login_provider_config_instances', $this->providers );
		}
	}

	/**
	 * Get the singleton instance of the registry.
	 */
	public static function get_instance(): self {
		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Returns the registered provider classes, keyed to their slugs.
	 *
	 * Filtered by 'graphql_login_registered_provider_configs'.
	 *
	 * @return array<string,class-string<\WPGraphQL\Login\Auth\ProviderConfig\ProviderConfig>>
	 */
	public function get_registered_providers(): array {
		if ( empty( $this->registered_providers ) ) {
			/**
			 * Filters the registered providers configs.
			 * Useful for removing a built-in provider, or for adding a custom one.
			 *
			 * @param array<string,class-string<\WPGraphQL\Login\Auth\ProviderConfig\ProviderConfig>> $registered_providers The registered provider config classes, keyed to their slug.
			 */
			$registered_providers = apply_filters(
				'graphql_login_registered_provider_configs',
				[
					Facebook::get_slug()  => Facebook::class,
					Generic::get_slug()   => Generic::class,
					Github::get_slug()    => GitHub::class,
					Google::get_slug()    => Google::class,
					Instagram::get_slug() => Instagram::class,
					LinkedIn::get_slug()  => LinkedIn::class,
					Password::get_slug()  => Password::class,
					SiteToken::get_slug() => SiteToken::class,
				]
			);

			// Sort providers alphabetically by slug.
			ksort( $registered_providers );

			$this->registered_providers = $registered_providers;
		}

		return $this->registered_providers;
	}

	/**
	 * Get the provider class for the given provider slug.
	 *
	 * @param string $provider_slug The provider slug of the instance to get.
	 *
	 * @throws \Exception When provider slug is not supported.
	 */
	public function get_provider_config( string $provider_slug ): ProviderConfig {
		if ( ! isset( $this->providers[ $provider_slug ] ) ) {
			throw new \Exception(
				sprintf(
				// translators: %s is the provider slug.
					esc_html__( 'Provider %s is not enabled.', 'wp-graphql-headless-login' ),
					esc_html( $provider_slug )
				)
			);
		}

		return $this->providers[ $provider_slug ];
	}

	/**
	 * Gets all the provider instances.
	 *
	 * @return array<string,\WPGraphQL\Login\Auth\ProviderConfig\ProviderConfig>
	 */
	public function get_providers(): array {
		return $this->providers;
	}
}
