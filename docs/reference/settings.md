# Settings Guide

<a href="https://raw.githubusercontent.com/axewp/wp-graphql-headless-login/main/assets/screenshot-1.jpeg" target="_blank"><img src="./../../assets/screenshot-1.jpeg" alt="Screenshot of the Headless Login for WPGraphQL settings page" width="600" height="auto" /><br />
Full-size screenshot</a>
</a>

1. **Show advanced settings**: This will show the plugin's and provider-specific advanced settings.
2. **Providers**: The list of registered authentication providers. Providers with a green dot are enabled. Click the provider name to edit its settings.
3. **Provider Settings**: The settings for the selected provider. The settings will vary depending on the provider, but usually include the following:
   1. **Client label**: The name of the Client that will be displayed to users.
   2. **Client ID**: The Client ID created by the provider.
   3. **Client secret**: The Client secret created by the provider.
   4. **Redirect URI**: The Redirect URI that the provider will send the authentication response to. This should be the URL of the Callback API route in your headless app.
4. **Login Settings**: The provider-specific settings for provisioning a user. The settings will vary depending on the provider but usually include the following:
   * **Login existing users**: If enabled, the plugin will attempt to login an existing WordPress user with the same email address as the Resource Owhener sent from the provider.
   * **Create new users**: If enabled, the plugin will create a new WordPress user if the Resource Owner does not have an existing linked account (or one with the same email address if `Login existing users` is enabled).
   * **Set authentication cookie**: If enabled, the plugin will set a WordPress authentication cookie on successful login. This is useful if you want to use your headless app and WP Admin with the same user session.
5. **Plugin Settings**: These settings are specific to the plugin and don't vary by provider:
   * **Regenerate JWT Secret**: This will regenerate the JWT site secret used to authenticate the GraphQL requests. Changing the secret will invalidate _all_ existing JWT tokens.
   **Note:** You can also set the JWT secret with code using the `WPGRAPHQL_LOGIN_JWT_SECRET_KEY` constant or [the `graphql_login_jwt_secret_key` filter](./filters.md#graphql_login_jwt_secret_key)
   * **Delete plugin data on deactivate**: If enabled, the plugin will delete all of its data when deactivated. This includes all of the plugin's settings including the client configurations. **Note:** The user meta will not be deleted.
6. **Access Control Settings**: These settings let you configure CORS headers, and tighten the security of GraphQl requests.
  * **Add Site URL to Access-Control-Allow-Origin**: If enabled, the plugin will add the WordPress site URL to the `Access-Control-Allow-Origin` header.
  * **Additional Authorized Domains**: A list of additional domains that will be allowed to make GraphQL requests. This is useful if you want to allow requests from a different domain than the WordPress site URL.
  * **Custom Headers**: A list of custom headers that will be added to Access-Control-Allow-Headers. This is useful if your custom implementation requires additional headers.
  * **Block Unauthorized Domains**: If enabled, the plugin will block all requests from unauthorized domains. This is useful if you want to prevent unauthorized requests from making GraphQL requests.

## Reference

- [Actions](/docs/reference/actions.md)
- [Filters](/docs/reference/filters.md)
- [Javascript API](/docs/reference/javascript-api.md)
- [Mutations](/docs/reference/mutations.md)
- [Queries](/docs/reference/queries.md)
- [Settings  ( 🎯 You are here )](/docs/reference/settings.md)
