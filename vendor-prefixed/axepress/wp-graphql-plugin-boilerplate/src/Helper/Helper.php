<?php
/**
 * Helper functions.
 *
 * @package AxeWP\GraphQL\Helper
 *
 * @license GPL-3.0-or-later
 * Modified by AxePress Development using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare( strict_types=1 );

namespace WPGraphQL\Login\Vendor\AxeWP\GraphQL\Helper;

if ( ! class_exists( '\WPGraphQL\Login\Vendor\AxeWP\GraphQL\Helper\Helper' ) ) {

	/**
	 * Class - Helper
	 */
	class Helper {
		/**
		 * The hook prefix for the plugin.
		 *
		 * @var string
		 */
		public static string $hook_prefix;

		/**
		 * Sets the hook prefix for the plugin.
		 *
		 * @param string $hook_prefix the hook prefix to use for this plugin.
		 */
		public static function set_hook_prefix( string $hook_prefix ): void {
			self::$hook_prefix = $hook_prefix;
		}

		/**
		 * Gets the hook prefix for the plugin.
		 */
		public static function hook_prefix(): string {
			if ( empty( self::$hook_prefix ) ) {
				_doing_it_wrong( __METHOD__, esc_html__( 'The hook prefix has not been set. Use set_hook_prefix() to set it.', 'wp-graphql-plugin-name' ), '0.0.8' );

				self::$hook_prefix = defined( 'AXEWP_PB_HOOK_PREFIX' ) ? AXEWP_PB_HOOK_PREFIX : 'graphql_pb';
			}

			return self::$hook_prefix;
		}
	}
}
