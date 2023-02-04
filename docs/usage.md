# Usage
_Headless authentication can be confusing. Before you continue, please review some [common terminology](terminology.md)._

## How it works
As follows is a _brief_ overview of how the plugin works.

1. Visit your WordPress site's admin Dashboard > GraphQL > Settings > Headless Login, and enable/configure the authentication providers you want to use and any other plugin settings.
2. Get the Client `authorizationUrl` from the `RootQuery.loginClients` field in GraphQL to use in your client app (or DIY with a frontend dependency like [NextAuth](https://next-auth.js.org/)).
3. When your frontend user clicks and visits the `authorizationUrl`, they'll be propmpted to login with the provider. If authorization is successful, they (along with the Authorization response ) will be redirected to the `redirectUri` you configured in the Provider's settings.
4. The Provider's authorization response should then be sent to the WordPress site via the `login` (or `linkUserIdentity`) mutation.

	Your WordPress server will then:
   	- Validate the authentication response.
   	- Fetch the Resource Owner's profile data from the provider.
   	- (optional) Create a new WordPress user and/or link an existing WordPress user to the Provider's Resource Owner.
   	- Generate and returns a JWT `authToken` and `refreshToken` for the user to use in future requests.
5. Your frontend app should and and store the `authToken` and `refreshToken` in a secure location (like a [Web Worker](https://thenewstack.io/leveraging-web-workers-to-safely-store-access-tokens/) or secure cookie) for future requests.
	- Authentication tokens should be passed in the Authorization Headers of future GraphQL requests.
	- Refresh tokens can be exchanged for a new auth token without requiring the user to reauthenticate.
	- **Woocommerce** - If you're using [WPGraphQL for WooCommerce](https://github.com/wp-graphql/wp-graphql-woocommerce), you should store and pass the `wooSessionToken` to the `woocommerce-session` header.
6. You can get a new `authToken` and `refreshToken` for the user in the response of an authenticated query by querying for the `RootQuery.viewer.auth.authToken`, or exchanged a stored `refreshToken` for a new `authToken` using the `refreshToken` mutation.
7. You can log the user out of all other devices by using `refreshUserSecret` or `revokeUserSecret` mutations.

## API Documentation
* [GraphQL Queries](queries.md)
* [GraphQL Mutations](mutations.md)
* [Javascript API](javascript-api.md)
* [WordPress Actions](actions.md)
* [WordPress Filters](filters.md)
