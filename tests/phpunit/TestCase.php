<?php
/**
 * Provide a base class for all unit tests by extending WPGraphQLUnitTestCase.
 *
 * @package WPGraphQL\Login\Tests
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Tests;

use ReflectionClass;
use Tests\WPGraphQL\TestCase\WPGraphQLUnitTestCase;
use WPGraphQL\Login\Admin\Settings\ProviderSettings;
use WPGraphQL\Login\Auth\ProviderRegistry;
use WPGraphQL\Login\Auth\TokenManager;
use WPGraphQL\Login\GraphQL\TypeRegistry;
use WPGraphQL\Login\Utils\Utils;

/**
 * Class - TestCase
 */
abstract class TestCase extends WPGraphQLUnitTestCase {
	/**
	 * {@inheritDoc}
	 *
	 * Prevents wp-phpunit failures with PHPUnit 11.5.
	 *
	 * @return array<string, array<string, list<string>>>
	 */
	public function getAnnotations(): array { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- Required compatibility method name.
		$class_reflection  = new ReflectionClass( static::class );
		$method_name       = $this->name();
		$method_reflection = $class_reflection->hasMethod( $method_name )
			? $class_reflection->getMethod( $method_name )
			: null;

		return [
			'class'  => self::parse_docblock_annotations( $class_reflection->getDocComment() ?: '' ),
			'method' => self::parse_docblock_annotations( $method_reflection?->getDocComment() ?: '' ),
		];
	}

	/**
	 * Parse selected docblock tags used in WP unit testing expectations.
	 *
	 * @param string $docblock Source docblock.
	 *
	 * @return array<string, list<string>>
	 */
	private static function parse_docblock_annotations( string $docblock ): array {
		if ( '' === trim( $docblock ) ) {
			return [];
		}

		$annotations = [];
		$tags        = [
			'ticket',
			'group',
			'expectedDeprecated',
			'expectedIncorrectUsage',
		];

		foreach ( $tags as $tag ) {
			$matches = [];
			preg_match_all( '/^[ \\t\\*]*@' . preg_quote( $tag, '/' ) . '\\s+([^\\r\\n\\*]+)/mi', $docblock, $matches );

			if ( ! empty( $matches[1] ) ) {
				$annotations[ $tag ] = array_values(
					array_filter(
						array_map( 'trim', $matches[1] ),
						static fn ( string $value ): bool => '' !== $value
					)
				);
			}
		}

		return $annotations;
	}

	/**
	 * Override to avoid PHPUnit scanning parent docblock metadata.
	 *
	 * @deprecated
	 */
	protected function checkRequirements(): void { // phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found
		parent::checkRequirements();
	}

	/**
	 * Resets the memoized static properties on the Utils class.
	 */
	protected function reset_utils_properties(): void {
		$reflection = new ReflectionClass( Utils::class );

		$reflection->setStaticPropertyValue( 'providers', [] );
		$reflection->setStaticPropertyValue( 'settings', [] );
		$reflection->setStaticPropertyValue( 'access_control', [] );
	}

	/**
	 * Resets the ProviderRegistry singleton.
	 */
	protected function reset_provider_registry(): void {
		( new ReflectionClass( ProviderRegistry::class ) )->setStaticPropertyValue( 'instance', null );
	}

	/**
	 * Resets the registered GraphQL types.
	 */
	protected function reset_type_registry(): void {
		TypeRegistry::$registry = [];
	}

	/**
	 * Sets the provider settings for the given slug.
	 *
	 * @param string              $slug   The provider slug.
	 * @param array<string,mixed> $config The provider settings.
	 */
	protected function set_client_config( string $slug, array $config ): void {
		update_option( ProviderSettings::$settings_prefix . $slug, $config );
		$this->reset_utils_properties();
		$this->reset_provider_registry();
	}

	/**
	 * Clears the provider settings for the given slug.
	 *
	 * @param string $slug The provider slug.
	 */
	protected function clear_client_config( string $slug ): void {
		update_option( ProviderSettings::$settings_prefix . $slug, [] );
		$this->reset_utils_properties();
		$this->reset_provider_registry();
	}

	/**
	 * Issues a new user secret, returning it along with the user auth and refresh tokens.
	 *
	 * @param int $user_id The user ID.
	 *
	 * @return array{site_secret:string,auth_token:?string,refresh_token:?string}
	 */
	protected function generate_user_tokens( int $user_id ): array {
		$original_user = get_current_user_id();
		wp_set_current_user( $user_id );

		$site_secret = wp_generate_password( 64, false, false );
		Utils::update_plugin_setting( 'jwt_secret_key', $site_secret );
		TokenManager::issue_new_user_secret( $user_id, false );
		$this->reset_utils_properties();

		$auth_token = TokenManager::get_auth_token( wp_get_current_user(), false );
		$this->reset_utils_properties();

		$refresh_token = TokenManager::get_refresh_token( wp_get_current_user(), false );
		$this->reset_utils_properties();

		wp_set_current_user( $original_user );

		return [
			'site_secret'   => $site_secret,
			'auth_token'    => $auth_token,
			'refresh_token' => $refresh_token,
		];
	}

	/**
	 * Runs the callback, returning its response along with the auth cookie events it fired.
	 *
	 * @param callable():mixed $callback The callback to run.
	 *
	 * @return array{response:mixed,events:array{set_auth_cookie:list<array<int,mixed>>,set_logged_in_cookie:list<array<int,mixed>>}}
	 */
	protected function capture_auth_cookie_events( callable $callback ): array {
		$events           = [
			'set_auth_cookie'      => [],
			'set_logged_in_cookie' => [],
		];
		$auth_cookie_hook = static function ( ...$args ) use ( &$events ): void {
			$events['set_auth_cookie'][] = $args;
		};
		$logged_in_hook   = static function ( ...$args ) use ( &$events ): void {
			$events['set_logged_in_cookie'][] = $args;
		};

		add_action( 'set_auth_cookie', $auth_cookie_hook, 10, 6 );
		add_action( 'set_logged_in_cookie', $logged_in_hook, 10, 6 );

		try {
			$response = $callback();
		} finally {
			remove_action( 'set_auth_cookie', $auth_cookie_hook );
			remove_action( 'set_logged_in_cookie', $logged_in_hook );
		}

		return [
			'response' => $response,
			'events'   => $events,
		];
	}

	/**
	 * Returns the debug messages from a GraphQL response.
	 *
	 * @param array<string,mixed> $response The GraphQL response.
	 *
	 * @return string[]
	 */
	protected function get_debug_messages( array $response ): array {
		return array_values( wp_list_pluck( $response['extensions']['debug'] ?? [], 'message' ) );
	}
}
