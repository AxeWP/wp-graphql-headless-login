# Javascript API

The WPGraphQL Headless Login settings screen is powered by [React](https://reactjs.org/) and the [@wordpress/block-editor](https://developer.wordpress.org/block-editor/reference-guides/). While most settings are customizable [using PHP](./filters.md#graphql_login_client_options_schema), there are times where you may wish to use Javascript to extend the settings screen using the Javascript API.

## The `wpGraphQLLogin` object

The `wpGraphQLLogin` Javascript object is available on the Headless Login Settings screen, and serves as the entrypoint for the API.

The object contains the following properties:

```tsx
wpGraphQLLogin {
  secret {
    hasKey: Boolean // Whether a JWT secret key has been set. 
    isConstant: Boolean // Whether the JWT secret key is defined with a Environment constant.
  },
  settings {
    plugin {
      [wpgraphql_login_setting_${setting_slug}] : { // e.g. `wpgraphql_login_settings_delete_data_on_deactivate`
        ...
      }
    }
    accessControl {
      [${settingName}] : {
        ...
      } // e.g. `additionalAuthorizedDomains`
    }
    providers {
      [wpgraphql_login_provider_${provider_slug}] : { // e.g. `wpgraphql_login_provider_google`
        ...
      }
    }
  }
  hooks {
    // The Hooks reference.
    // https://developer.wordpress.org/block-editor/reference-guides/filters/
  }
}
```

## Javascript Filters

### `graphql_login_custom_client_settings`

Filters the OAuth Client's custom settings. Useful for adding a custom settings panel to the client.

```js
wpGraphQLLogin.hooks.applyFilters(
  'graphql_login_custom_client_settings,
  CustomFragment,
  clientSlug,
  client
)
```

### `graphql_login_custom_plugin_options`

Filters the OAuth Client's custom plugin options. Useful for adding additional plugin-wide settings.

```js
wpGraphQLLogin.hooks.applyFilters(
  'graphql_login_custom_plugin_options',
  CustomFragment,
)
```
