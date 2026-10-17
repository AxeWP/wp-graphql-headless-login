<?php
/**
 * Tests the plugin's GraphQL schema.
 *
 * @package WPGraphQL\Login\Tests\Integration\GraphQL
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Tests\Integration\GraphQL;

use PHPUnit\Framework\Attributes\CoversClass;
use WPGraphQL\Login\Auth\ProviderRegistry;
use WPGraphQL\Login\GraphQL\Type\Enum\GoogleProviderPromptTypeEnum;
use WPGraphQL\Login\GraphQL\Type\Enum\ProviderEnum;
use WPGraphQL\Login\GraphQL\Type\Fields\RootQuery;
use WPGraphQL\Login\GraphQL\Type\Input\OAuthProviderResponseInput;
use WPGraphQL\Login\GraphQL\Type\Input\PasswordProviderResponseInput;
use WPGraphQL\Login\GraphQL\Type\Mutation\LinkUserIdentity;
use WPGraphQL\Login\GraphQL\Type\Mutation\Login;
use WPGraphQL\Login\GraphQL\Type\Mutation\Logout;
use WPGraphQL\Login\GraphQL\Type\Mutation\RefreshToken;
use WPGraphQL\Login\GraphQL\Type\Mutation\RefreshUserSecret;
use WPGraphQL\Login\GraphQL\Type\Mutation\RevokeUserSecret;
use WPGraphQL\Login\GraphQL\Type\WPInterface\ClientOptions as ClientOptionsInterface;
use WPGraphQL\Login\GraphQL\Type\WPInterface\LoginOptions as LoginOptionsInterface;
use WPGraphQL\Login\GraphQL\Type\WPObject\AuthenticationData;
use WPGraphQL\Login\GraphQL\Type\WPObject\Client;
use WPGraphQL\Login\GraphQL\Type\WPObject\ClientOptions;
use WPGraphQL\Login\GraphQL\Type\WPObject\LinkedIdentity;
use WPGraphQL\Login\GraphQL\Type\WPObject\LoginOptions;
use WPGraphQL\Login\GraphQL\TypeRegistry;
use WPGraphQL\Login\Tests\TestCase;
use WPGraphQL\Login\Vendor\AxeWP\Common\GraphQL\Abstracts\FieldsType;
use WPGraphQL\Login\Vendor\AxeWP\Common\GraphQL\Abstracts\MutationType;

/**
 * Tests that the types registered by the plugin are fully documented, as required by `npm run lint:schema`.
 */
#[CoversClass( GoogleProviderPromptTypeEnum::class )]
#[CoversClass( ProviderEnum::class )]
#[CoversClass( OAuthProviderResponseInput::class )]
#[CoversClass( PasswordProviderResponseInput::class )]
#[CoversClass( ClientOptionsInterface::class )]
#[CoversClass( LoginOptionsInterface::class )]
#[CoversClass( AuthenticationData::class )]
#[CoversClass( Client::class )]
#[CoversClass( ClientOptions::class )]
#[CoversClass( LinkedIdentity::class )]
#[CoversClass( LoginOptions::class )]
#[CoversClass( RootQuery::class )]
#[CoversClass( LinkUserIdentity::class )]
#[CoversClass( Login::class )]
#[CoversClass( Logout::class )]
#[CoversClass( RefreshToken::class )]
#[CoversClass( RefreshUserSecret::class )]
#[CoversClass( RevokeUserSecret::class )]
class SchemaTest extends TestCase {
	/**
	 * {@inheritDoc}
	 */
	protected function setUp(): void {
		parent::setUp();

		// The logout mutation is only registered when enabled.
		add_filter(
			'graphql_login_cookie_setting',
			static fn ( $value, string $option_name ) => 'hasLogoutMutation' === $option_name ? true : $value,
			10,
			2
		);

		// Repopulate the registry, since `graphql_init` has already fired.
		$this->reset_type_registry();
		TypeRegistry::init();
		$this->clearSchema();
	}

	/**
	 * {@inheritDoc}
	 */
	protected function tearDown(): void {
		$this->clearSchema();

		parent::tearDown();
	}

	/**
	 * Tests that the plugin's types, fields, arguments and enum values all have descriptions.
	 */
	public function test_plugin_types_have_descriptions(): void {
		$query = '
			query {
				__schema {
					types {
						name
						description
						fields {
							name
							description
							args {
								name
								description
							}
						}
						inputFields {
							name
							description
						}
						enumValues {
							name
							description
						}
					}
				}
			}
		';

		$actual = $this->graphql( compact( 'query' ) );

		$this->assertArrayNotHasKey( 'errors', $actual );

		$types = array_column( $actual['data']['__schema']['types'], null, 'name' );

		[ $type_names, $fields_by_type ] = $this->get_plugin_schema();

		$undocumented = [];

		foreach ( $type_names as $type_name ) {
			$this->assertArrayHasKey( $type_name, $types, sprintf( 'The %s type should be in the schema.', $type_name ) );

			$type = $types[ $type_name ];

			if ( empty( $type['description'] ) ) {
				$undocumented[] = $type_name;
			}

			foreach ( [ 'fields', 'inputFields', 'enumValues' ] as $key ) {
				$undocumented = array_merge( $undocumented, $this->get_undocumented( $type_name, $type[ $key ] ?? [] ) );
			}
		}

		foreach ( $fields_by_type as $type_name => $field_names ) {
			$fields = array_filter(
				$types[ $type_name ]['fields'],
				static fn ( array $field ): bool => in_array( $field['name'], $field_names, true )
			);

			$this->assertCount( count( $field_names ), $fields, sprintf( 'The %s fields should be registered to %s.', implode( ', ', $field_names ), $type_name ) );

			$undocumented = array_merge( $undocumented, $this->get_undocumented( $type_name, $fields ) );
		}

		$this->assertSame( [], $undocumented, 'The schema members should have descriptions.' );
	}

	/**
	 * Gets the type names registered by the plugin, and the fields it registers to other types.
	 *
	 * @return array{0:string[],1:array<string,string[]>}
	 */
	private function get_plugin_schema(): array {
		$this->assertNotEmpty( TypeRegistry::$registry );

		$type_names = [];
		$fields     = [
			'RootMutation' => [],
			'User'         => [ 'auth' ],
		];

		foreach ( array_unique( TypeRegistry::$registry ) as $class ) {
			if ( is_a( $class, MutationType::class, true ) ) {
				$name = $class::get_type_name();

				$fields['RootMutation'][] = lcfirst( $name );
				$type_names[]             = ucfirst( $name ) . 'Input';
				$type_names[]             = ucfirst( $name ) . 'Payload';
				continue;
			}

			if ( is_a( $class, FieldsType::class, true ) ) {
				$fields[ $class::get_type_name() ] = array_keys( $class::get_fields() );
				continue;
			}

			// These are registered once per provider.
			if ( in_array( $class, [ ClientOptions::class, LoginOptions::class ], true ) ) {
				continue;
			}

			$type_names[] = $class::get_type_name();
		}

		foreach ( array_keys( ProviderRegistry::get_instance()->get_registered_providers() ) as $slug ) {
			$type_names[] = graphql_format_type_name( ucfirst( $slug ) . 'ClientOptions' );
			$type_names[] = graphql_format_type_name( ucfirst( $slug ) . 'LoginOptions' );
		}

		return [ $type_names, $fields ];
	}

	/**
	 * Gets the schema members (and their args) missing a description.
	 *
	 * @param string                         $type_name The parent type name.
	 * @param array<int,array<string,mixed>> $members   The fields, input fields, or enum values.
	 *
	 * @return string[]
	 */
	private function get_undocumented( string $type_name, array $members ): array {
		$undocumented = [];

		foreach ( $members as $member ) {
			$path = $type_name . '.' . $member['name'];

			if ( empty( $member['description'] ) ) {
				$undocumented[] = $path;
			}

			foreach ( $member['args'] ?? [] as $arg ) {
				if ( empty( $arg['description'] ) ) {
					$undocumented[] = $path . '(' . $arg['name'] . ':)';
				}
			}
		}

		return $undocumented;
	}
}
