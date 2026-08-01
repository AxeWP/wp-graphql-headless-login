# GraphQL Queries

## Querying login Clients

```graphql
query getClients {
  # @todo add sort/filtering
  loginClients {
    # The list of enabled Clients
    authorizationUrl # The authorizationUrl for the Provider
    clientOptions {
      clientId
      clientSecret
      redirectUri # The provider will redirect to this URI after authorization
      ... on FacebookClientOptions {
        # Provider-specific options
        enableBetaTier
        graphApiVersion
        scope
      }
    }
    isEnabled # Disabled Providers will not create a Client instance
    loginOptions {
      # Options regarding how to handle user matching and creation.
      createUserIfNoneExists
      useAuthenticationCookie # Whether to set the WordPress authentication cookie on successful login.
      ... on FacebookLoginOptions {
        linkExistingUsers
        ...FBLoginOptionsFrag # other provider-specific options
      }
    }
    name # The provider name
    order # The order in which the provider will be displayed
    provider # The Provider used to generate the client.
  }
}
```

## Querying user authentication data

```graphql
query getUserWithAuthenticationData($id: ID!, $idType: UserNodeIdTypeEnum) {
  user(id: $id, idType: $idType) {
    username
    email
    # other user fields
    auth {
      # The Authentication data object.
      authToken # A new JWT auth token that can be used for future requests.
      authTokenExpiration
      isUserSecretRevoked # Whether or not the user secret has been revoked.
      linkedIdentities {
        # The list of linked identities.
        id # The Provider's Resource Owner ID
        provider # The Provider
      }
      refreshToken # A new JWT refresh token
      refreshTokenExpiration
      userSecret # The current user secret
    }
  }
}
```

If [WPGraphQL for WooCommerce](https://github.com/wp-graphql/wp-graphql-woocommerce) (v1.0.0+) is installed, the `auth` field is also available on the `Customer` Object type, so you can request auth tokens and customer data in one query.

WooCommerce session tokens are separate from `auth`, and come from WPGraphQL for WooCommerce. Depending on its **Session Token Type** setting, query `Customer.sessionToken` / `User.wooSessionToken` (Legacy, the default) or `Customer.cartToken` / `User.cartToken` (Store API). See [Add Support for WPGraphQL for WooCommerce](/docs/recipes/server-side-auth-next-api-routes.md#8-optional-add-support-for-wpgraphql-for-woocommerce).

## Reference

- [Actions](/docs/reference/actions.md)
- [Filters](/docs/reference/filters.md)
- [Javascript API](/docs/reference/javascript-api.md)
- [Mutations](/docs/reference/mutations.md)
- [Queries ( 🎯 You are here )](/docs/reference/queries.md)
- [Settings](/docs/reference/settings.md)
