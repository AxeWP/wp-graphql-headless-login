# GraphQL Mutations

## Login with an OAuth2/OpenID authorization response
```graphql
mutation loginWithOAuth(
  $provider: LoginProviderEnum!, # One of the enabled Authentication Provider types.
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
  }
}
```

## Login with a traditional WordPress username/password
```graphql
mutation loginWithPassword(
  $username: String!,
  $password: String!,
) {
  loginWithPassword(
    input: {
      username: $username,
      password: $password,
    }
  ) {
    authToken
    authTokenExpiration
    refreshToken
    refreshTokenExpiration
    user { # The authenticated WordPress user.
      ...MyUserFrag
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
