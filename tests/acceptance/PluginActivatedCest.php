<?php

class PluginActivatedCest {
	public function seePluginActivated( AcceptanceTester $I ) {
		$I->loginAsAdmin();
		$I->amOnPluginsPage();
		$I->seePluginActivated( 'headless-login-for-wpgraphql' );
	}
}
