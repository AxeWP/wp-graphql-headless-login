<?php
/**
 * A Facebook provider config that uses the mocked provider and HTTP client.
 *
 * @package WPGraphQL\Login\Tests\Fixtures
 */

declare( strict_types = 1 );

namespace WPGraphQL\Login\Tests\Fixtures;

use WPGraphQL\Login\Auth\ProviderConfig\OAuth2\Facebook;
use WPGraphQL\Login\Auth\ProviderConfig\OAuth2\OAuth2Config;

/**
 * A Facebook provider config wired to the mocked provider and HTTP client.
 */
class FooFacebookProviderConfig extends Facebook {
	/**
	 * Builds the config with the mocked provider, bypassing the parent's hardcoded provider class.
	 */
	public function __construct() {
		OAuth2Config::__construct( FooFacebookProvider::class );

		$this->provider->setHttpClient( MockHttpClient::create() );
	}
}
