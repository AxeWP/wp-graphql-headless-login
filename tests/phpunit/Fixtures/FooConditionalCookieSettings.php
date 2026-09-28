<?php
/**
 * Cookie settings whose fields depend on another field with each conditional logic operator.
 *
 * @package WPGraphQL\Login\Tests\Fixtures
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Tests\Fixtures;

use WPGraphQL\Login\Admin\Settings\CookieSettings;

/**
 * Replaces the cookie settings config with fields that depend on the `level` field.
 */
class FooConditionalCookieSettings extends CookieSettings {
	/**
	 * {@inheritDoc}
	 */
	public function get_config(): array {
		$config = [
			'level' => [
				'description'       => 'The field the others depend on.',
				'label'             => 'Level',
				'type'              => 'integer',
				'default'           => 5,
				'sanitize_callback' => 'absint',
			],
		];

		$rules = [
			'whenEqual'          => [ '==', 5 ],
			'whenNotEqual'       => [ '!=', 5 ],
			'whenGreater'        => [ '>', 4 ],
			'whenLess'           => [ '<', 6 ],
			'whenGreaterOrEqual' => [ '>=', 6 ],
			'whenLessOrEqual'    => [ '<=', 4 ],
			'whenUnknownOp'      => [ '~', 5 ],
			'whenMissingGroup'   => [ '==', 5, 'not_a_setting_group.level' ],
		];

		foreach ( $rules as $key => $rule ) {
			$config[ $key ] = [
				'description'       => 'A dependent field.',
				'label'             => $key,
				'type'              => 'boolean',
				'default'           => false,
				'sanitize_callback' => 'rest_sanitize_boolean',
				'conditionalLogic'  => [
					'slug'     => $rule[2] ?? 'level',
					'operator' => $rule[0],
					'value'    => $rule[1],
				],
			];
		}

		return $config;
	}
}
