<?php
namespace Tests\WPGraphQL\Login\Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class GraphQL extends \Codeception\Module {
	/**
	 * Sends GraphQL and returns a response
	 *
	 * @see https://github.com/wp-graphql/wp-graphql-woocommerce/blob/e4f7da8fdb631dc622e522347d586394f5f596f8/tests/_support/Helper/GraphQLE2E.php
	 *
	 * @param string     $query
	 * @param array|null $variables
	 * @param array|null $request_headers
	 */
	public function sendGraphQLRequest( $query, $variables = null, $request_headers = [] ): array {
		$rest = $this->getModule( 'REST' );

		// Set request headers
		$rest->haveHttpHeader( 'Content-Type', 'application/json' );

		foreach ( $request_headers as $header => $value ) {
			$rest->haveHttpHeader( $header, $value );
		}

		// Send request.
		$rest->sendPost(
			'/graphql',
			json_encode(
				[
					'query'     => $query,
					'variables' => $variables,
				]
			)
		);

		// Get response.
		$rest->seeResponseIsJson();
		$response = json_decode( $rest->grabResponse(), true );

		// use --debug flag to view
		codecept_debug( json_encode( $response, JSON_PRETTY_PRINT ) );

		// Confirm success.
		$rest->seeResponseCodeIs( 200 );

		return $response;
	}

	/**
	 * Sets the GraphQL debug mode to on.
	 */
	public function haveGraphQLDebug() {
		/** @var \lucatume\WPBrowser\Module\WPDb $wpdb */
		$wpdb = $this->getModule( 'lucatume\WPBrowser\Module\WPDb' );

		$graphql_settings = $wpdb->grabOptionFromDatabase( 'graphql_general_settings' ) ?: [];

		$graphql_settings['debug_mode_enabled'] = 'on';

		$wpdb->haveOptionInDatabase( 'graphql_general_settings', $graphql_settings );
	}
}
