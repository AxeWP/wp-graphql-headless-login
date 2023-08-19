<?php

class PluginSettingsTabCest {
	public function seeSettingsTab( AcceptanceTester $I ) {
		$I->loginAsAdmin();
		$I->amOnPluginsPage();
		$I->amOnPage( '/wp-admin/admin.php?page=graphql-settings' );
		$I->see( 'Headless Login' );
	}
}
