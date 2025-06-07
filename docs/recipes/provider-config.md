# Supporting Custom Providers with the `ProviderConfig` PHP Class.

Headless Login for WPGraphQL allows you to add support for other authentication provides by extending the  [`WPGraphQL\Login\Auth\ProviderConfig\ProviderConfig` PHP class](../../src/Auth/ProviderConfig/ProviderConfig.php). This class will handle the authentication flow for your provider, as well as its settings and GraphQL fields.

The `ProviderConfig` class also has a child class called `OAuth2Config` that you can extend to handle OAuth2 providers. This class is built to work seamlessly with League's [OAuth2 Client](https://oauth2-client.thephpleague.com/) library, however you can extend it to work with any OAuth2 library, or use the parent `ProviderConfig` class to handle your own custom authentication flow, such as [SAML](https://en.wikipedia.org/wiki/Security_Assertion_Markup_Language), [keycloak](https://www.keycloak.org/), or practically anything else.

> **Note:** Headless Login for WPGraphQL dog-foods its own APIs. For real-world examples of how to use the `ProviderConfig` class, you can check out the included provider configs in the [src/Auth/ProviderConfig](../../src/Auth/ProviderConfig) directory.

## Example: Registering a `OAuth2Config` class with League's OAuth2 Client library.

In this example, we'll register a `ProviderConfig` class for the [GitHub OAuth2 provider](https://developer.github.com/apps/building-oauth-apps/authorizing-oauth-apps/). We'll use the [League's OAuth2 Client](https://oauth2-client.thephpleague.com/) library to handle the OAuth2 flow.

### 1. Include the OAut2h Client library in your project.

```bash
composer require league/oauth2-github
```

### 2. Create a `ProviderConfig` class for GitHub.

```php
<?php

namespace MyPlugin\CustomProviderConfigs;

// This is from the OAuth2 Client provider library we installed in step 1.
use League\OAuth2\Client\Provider\Github;

/**
 * Class GitHubProviderConfig
 */
class GitHubProviderConfig extends \WPGraphQL\Login\Auth\ProviderConfig\OAuth2\OAuth2Config {
  /**
   * The Constructor.
   */
  public function __construct() {
    // Pass the League OAuth2 Client Provider class to the parent constructor.
    parent::__construct( Github::class );
  }

  /**
   * Get the provider's name.
   *
   * This is how it will appear in the Settings Page.
   */
  public static function get_name(): string {
    return __( 'GitHub', 'my-plugin' );
  }

  /**
   * Get the provider's slug.
   *
   * This is how it will be used in the GraphQL schema.
   */
  public static function get_slug(): string {
    return 'github';
  }

  /**
   * Defines the Client Options REST API schema for the provider.
   *
   * This is used to display and save the Client Options settings the Settings Page.
   *
   * Note: This method will inherit the default options schema, so you only need to add the fields specific to GitHub.
   */
  protected static function client_options_schema() : array {
    return [
      'scope' => [
        'type'        => 'array',
        'description' => static fn () => __( 'The scopes to request from the provider.', 'my-plugin' ),
        'help'        => __( 'See https://some-link for a list of available scopes.', 'my-plugin' ),
        'order'       => 10,
        'advanced'    => true,
        'items'       => [
          'type' => 'string',
        ],
      ],
    ];
  }

  /**
   * Defines the Client Options GraphQL fields for the provider.
   *
   * These fields will be available in the generated ObjectType for the LoginClientOptions interface.
   *
   * Note: This method will inherit the default options schema, so you only need to add the fields specific to GitHub.
   */
   protected static function client_options_fields() : array {
    return [
      'scope' => [
        'type'        => [ 'list_of' => 'String' ],
        'description' => static fn () => __( 'The scope to request from the provider.', 'wp-graphql-headless-login' ),
      ],
    ];
  }

  /**
   * Maps the ResourceOwner data to the WP_User arguments.
   */
   public function get_user_data( array $owner_details ) : array {
    $name_parts = explode( ' ', $owner_details['name'] ?? '' );

    // Get a string from all parts but last.
    $first_name = implode( ' ', array_slice( $name_parts, 0, -1 ) ) ?: null;
    $last_name  = count( $name_parts ) > 1 ? end( $name_parts ) : null;

    // IRL, you should sanitize these values.
    return [
      'user_login'       => $owner_details['login'] ?? null,
      'user_email'       => $owner_details['email'] ?? null,
      'first_name'       => $first_name,
      'last_name'        => $last_name,
      'description'      => $owner_details['bio'] ?? null,
      'user_url'         => $owner_details['blog'] ?? null,
      'subject_identity' => (string) $owner_details['id'],
    ];
  }

  /**
   * Get the provider's Client Options.
   *
   * This maps the Client Options stored in the database that we defined in the client_options_schema() method to the format that the League OAuth2 Client expects.
   *
   * @param array $settings The provider settings array from the WP database.
   */
  protected function get_options( array $settings ) : array {
    return [
      'clientId'     => $settings['clientId'] ?? null,
      'clientSecret' => $settings['clientSecret'] ?? null,
      'redirectUri'  => $settings['redirectUri'] ?? null,
      'scope'        => ! empty( $settings['scope'] ) ? $settings['scope'] : [],
    ];
  }
}
```

### 3. Register the `ProviderConfig` class with [the `graphql_login_registered_provider_configs` filter](./reference/filters.md#graphql_login_registered_provider_configs).

```php
<?php
add_filter(
  'graphql_login_registered_provider_configs',
  function( array $provider_configs ) {
    // Give the provider a unique slug, and pass the ProviderConfig class name.
    $provider_configs['github'] = \MyPlugin\CustomProviderConfigs\GitHubProviderConfig::class;

    return $provider_configs;
} );
```
_For more flexibility and customization when adding custom providers, see the [ProviderConfig](../../src/Auth/ProviderConfig/ProviderConfig.php) and [OAuth2Config](../../src/Auth/ProviderConfig/OAuth2/OAuth2Config.php) classes._
