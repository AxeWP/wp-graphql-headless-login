# GraphQL Queries

## Querying login Clients

```graphql
query getClients {
  # @todo add sort/filtering
  loginClients { # The list of enabled Clients
    authorizationUrl # The authorizationUrl for the Provider
    clientOptions {
      clientId
      clientSecret
      redirectUri # The provider will redirect to this URI after authorization
      ... on FacebookClientOptions { # Provider-specific options
        enableBetaTier
        graphApiVersion
        scope
      }
    }
    isEnabled # Disabled Providers will not create a Client instance
    loginOptions { # Options regarding how to handle user matching and creation.
      createUserIfNoneExists
      linkExistingUsers
      ... on FacebookLoginOptions {
        ...FBLoginOptionsFrag # Provider-specific options
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
query getUserWithAuthenticationData( $id: ID!, $idType: UserNodeIdTypeEnum ) {
  user( id: $id, idType: $idType ) {
    username
    email
    # other user fields
    auth { # The Authentication data object.
      authToken # A new JWT auth token that can be used for future requests.
      authTokenExpiration
      isUserSecretRevoked # Whether or not the user secret has been revoked.
      linkedIdentities { # The list of linked identities.
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
