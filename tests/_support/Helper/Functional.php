<?php
namespace Tests\WPGraphQL\Login\Helper;

use WPGraphQL\Login\Admin\Settings\PluginSettings;
use WPGraphQL\Login\Auth\TokenManager;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Functional extends \Codeception\Module {
	public function generateUserTokens( int $user_id ): array {
		/** @var \lucatume\WPBrowser\Module\WPDb $wpdb */
		$wpdb = $this->getModule( 'lucatume\WPBrowser\Module\WPDb' );
		/** @var \Tests\WPGraphQL\Login\Helper\Helper $helper */
		$helper = $this->getModule( '\Tests\WPGraphQL\Login\Helper\Helper' );

		$site_secret = wp_generate_password( 64, false, false );

		$wpdb->haveOptionInDatabase( PluginSettings::get_slug() . 'jwt_secret_key', $site_secret );

		// Reset the utils properties to ensure the site secret is used.

		$helper->reset_utils_properties();

		$wpdb->haveUserMetaInDatabase( $user_id, 'graphql_login_secret', uniqid( 'graphql_login_secret_', true ) );
		$wpdb->haveUserMetaInDatabase( $user_id, 'graphql_login_secret_revoked', false );

		$user = new \WP_User( $user_id );
		$helper->reset_utils_properties();

		$auth_token = TokenManager::get_auth_token( $user, false );
		$helper->reset_utils_properties();

		$refresh_token = TokenManager::get_refresh_token( $user, false );
		$helper->reset_utils_properties();

		return [
			'site_secret'   => $site_secret,
			'auth_token'    => $auth_token,
			'refresh_token' => $refresh_token,
		];
	}
}
