<?php

class ActivationCest {
	public function seePluginActivated( FunctionalTester $I ) {
		$I->loginAsAdmin();
		$I->amOnPluginsPage();
		$I->seePluginActivated( 'headless-login-for-wpgraphql' );
	}

	public function seeSettingsTab( FunctionalTester $I ) {
		$I->loginAsAdmin();
		$I->amOnPluginsPage();
		$I->amOnPage( '/wp-admin/admin.php?page=graphql-settings' );
		$I->see( 'Headless Login', '#wpgraphql_login_settings-tab' );
	}
}
