<?php
/**
 * Stubs for the WPGraphQL for WooCommerce (WooGraphQL) symbols referenced by this plugin.
 *
 * WooGraphQL is not installable via Composer, so the handful of classes used
 * by WoocommerceSchemaFilters are stubbed here for PHPStan.
 *
 * @package WPGraphQL/Login
 */

// phpcs:ignoreFile

namespace WPGraphQL\WooCommerce\Utils {
	class QL_Session_Handler extends \WC_Session_Handler {
		public function build_token(): string {
			return '';
		}
	}
}

namespace WPGraphQL\WooCommerce\Model {
	class Customer {
		public function __construct( int $id ) {}
	}
}
