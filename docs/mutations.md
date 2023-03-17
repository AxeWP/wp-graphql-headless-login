# GraphQL Mutations

## Login with an OAuth2/OpenID authorization response
```graphql
mutation login(
  $provider: LoginProviderEnum!, # One of the enabled Authentication Provider types. e.g. FACEBOOK, or GENERIC_OAUTH2
  $code:     String!,            # The Authorization Code sent by the Authentication Provider to the frontend's callback URI.
  $state:    String,             # A randomly-generated string used to verify the authenticity of the response sent by the Provider.
) {
  login(
    input: {
      provider: $provider
      oauthResponse: {
        state: $state,
        code: $code, 
      }
    }
  ) {
    authToken
    authTokenExpiration
    refreshToken
    refreshTokenExpiration
    user { # The authenticated WordPress user.
      ...MyUserFrag
    }
    # The following fields are available if WPGraphQL for WooCommerce is installed.
    wooSessionToken
    customer {
      ...MyCustomerFrag
    }
  }
}
```

## Login with a traditional WordPress username/password
```graphql
mutation loginWithPassword(
  $username: String!,
  $password: String!,
) {
  login(
    input: {
      provider: PASSWORD, # This tells the mutation to use the WordPress username/password authentication method.
      credentials: {      # This is the input required for the PASSWORD provider.
        username: $username,
        password: $password,
      }
    }
  ) {
    authToken
    authTokenExpiration
    refreshToken
    refreshTokenExpiration
    user { # The authenticated WordPress user.
      ...MyUserFrag
    }
    # The following fields are available if WPGraphQL for WooCommerce is installed.
    wooSessionToken
    customer {
      ...MyCustomerFrag
    }
  }
}
```

## Login with a Site Token and User Identity.

**Note**: for the Site Token provider to work, you need to set the request header that your defined in the Provider Config settings, and have `Access Control : Block unauthorized domains`.

This mutation should **only be used server-side** so as not to expose the Site Token.

```graphql
mutation loginAsIdentity(
  $identity: String! # This is the value used to identify the WordPress user.
) {
  login(
    input: {
      provider: SITETOKEN, # This tells the mutation to use the Site Token provider.
      identity: $identity
    }
  ) {
    authToken
    authTokenExpiration
    refreshToken
    refreshTokenExpiration
    user { # The authenticated WordPress user.
      ...MyUserFrag
    }
    # The following fields are available if WPGraphQL for WooCommerce is installed.
    wooSessionToken
    customer {
      ...MyCustomerFrag
    }
  }
}
```

## Exchange the Refresh Token for a new Auth Token
```graphql
mutation refreshToken(
  $token: String! # The user's refreshToken.
) {
  refreshToken( input: {refreshToken: $token} ) {
    authToken # The new auth token for the user.
    success
  }
}
```


## Manually link the WordPress user to a Provider's Resource Owner

```graphql
mutation linkUserIdentity(
  $provider: LoginProviderEnum!, # One of the enabled Authentication Provider types.
  $userId:   ID!                 # The user ID, accepts either a global or database ID.
  $code:     String!,            # The Authorization Code sent by the OAuth2 Provider to the frontend's callback URI. 
  $state:    String,             # A randomly-generated string used to verify the authenticity of the response.
 {
  linkUserIdentity(
    input: {
      provider: $provider
      userId: $userId
      oauthResponse: {
        state: $state,
        code: $code, 
      }
    }
  ) {
    success
    user {
      # other user fields
      auth {
        # other authentication fields
        linkedIdentities { # The external identities linked to this user.
          id       # The ResourceOwner ID on the provider
          provider # The provider slug.
        }
      }
    }
  }
}
```


## Revoke the User Secret
```graphql
mutation revokeUserSecret(
  $userId: ID! # Either the global or database ID.
) {
  revokeUserSecret(input: {userId: $userId}) {
    revokedUserSecret # The previous user secret.
    success
  }
}
```

## Refresh the User Secret
```graphql
mutation refreshUserSecret(
  $userId: ID! # Either the global or database ID.
) {
  refreshUserSecret(input: {userId: $userId}) {
    authToken         # The new auth token
    refreshToken      # the new Refresh token
    userSecret        # the new user secret.
    revokedUserSecret # the old user secret.
    success
  }
}

```
