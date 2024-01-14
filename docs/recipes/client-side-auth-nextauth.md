# Recipe: Client-side Authentication flow with NextAuth.js

This recipe explains how use a client-side authentication flow to authenticate and match users on your WordPress site, using the Password and SiteToken providers included with Headless Login for WPGraphQL.

While the example below uses [NextAuth.js](https://next-auth.js.org/), the same approach can be used with any client-side identity flow.

## Table of Contents

- [Recipe: Client-side Authentication flow with NextAuth.js](#recipe-client-side-authentication-flow-with-nextauthjs)
	- [Table of Contents](#table-of-contents)
	- [🐲 Warning: Dragons Ahead](#-warning-dragons-ahead)
	- [1. Configure the Password and SiteToken providers](#1-configure-the-password-and-sitetoken-providers)
	- [2. Create the \[...nextauth\].js API Route](#2-create-the-nextauthjs-api-route)
	- [3. Configure the NextAuth provider(s) and the `signIn` callback.](#3-configure-the-nextauth-providers-and-the-signin-callback)
	- [4. Configure the `jwt` callback.](#4-configure-the-jwt-callback)
	- [5. Configure the `session` callback](#5-configure-the-session-callback)
	- [6. Use the `authToken` in your GraphQL requests.](#6-use-the-authtoken-in-your-graphql-requests)
	- [7. Using the session data in your frontend components.](#7-using-the-session-data-in-your-frontend-components)
	- [8. (Optional) Configure NextAuth to support password authentication.](#8-optional-configure-nextauth-to-support-password-authentication)

## 🐲 Warning: Dragons Ahead

The `SiteToken` provider is a powerful feature of Headless Login for WPGraphQL, and is _ripe for abuse_.

The way it works is by allowing you to "force authenticate" as any WordPress user with just a custom secret key, and any piece of user metadata that you define on the backend.

This is why the `SiteToken` provider can only be used when GraphQL requests are restricted to authenticated domains. However, even with this restriction, you should be very careful about how you use this feature.

We **strongly** recommend you use a [server-side authentication flow](./server-side-auth-next-api-routes.md) and only use the `SiteToken` provider as a last resort.

## 1. Configure the Password and SiteToken providers

For more information on configuring the providers, see the [Settings Guide](../reference/settings.md).

> Note: Due to the [potential for abuse](#🐲-warning-dragons-ahead), the Site Token provider can only be used when GraphQL requests are restricted to authenticated domains. You will need to enable the `Block Unauthorized Domains` setting in the [Access Control settings](../reference/settings.md).

## 2. Create the [...nextauth].js API Route

(This step assumes you have already set up NextAuth.js. If not, see the [NextAuth.js Getting Started Guide](https://next-auth.js.org/getting-started/example). )

In your headless app, you will need to create the [the `[...nextauth].js` API route](https://next-auth.js.org/getting-started/example#add-api-route) used by NextAuth to configure the providers and callbacks used by the authentication flow.

We scaffold this file now, and fill in the logic in the next step.


```jsx
// pages/api/auth/[...nextauth].js

const providers = []; // We'll define this later.

const callbacks = {}; // We'll define this later.

export const authOptions : {
	providers,
	callbacks,
	debug: process.env.NODE_ENV === 'development', // Enable debug mode in development.
	session: {
		strategy: 'jwt', // We'll be using the JSON Web Tokens from the Headless Login plugin.
	},
}

export default NextAuth(authOptions);
```

## 3. Configure the NextAuth provider(s) and the `signIn` callback.

Now we need to configure the provider(s) we want to use with NextAuth.js.

NextAuth.js supports a number of [authentication providers](https://next-auth.js.org/configuration/providers), which we can support seamlessly with Headless Login for WPGraphQL.

Providers are configured by adding them to the `providers` array in the `[...nextauth].js` that we scaffolded in the previous step.

```jsx
// pages/api/auth/[...nextauth].js
const providers = [
// Add any other providers here. E.g.:
	GoogleProvider( {
		clientId: process.env.NEXT_PUBLIC_GOOGLE_CLIENT_ID,
		clientSecret: process.env.GOOGLE_CLIENT_SECRET,
	} ),
];
```

While this is all that is required to support the provider on the client side, we still need a way to authenticate the user with WordPress. We'll do this by defining the `signIn` callback in the `callbacks` object we scaffolded earlier.

```js
// pages/api/auth/[...nextauth].js

const callbacks = {
	/**
	 * The signIn callback is called when a user signs in.
	 * We use it to match the user with a user on WordPress, and get the user and auth data we'll use to manage the session.
	 */
	signIn: async ( user, account, profile ) => {
		try {
			// This example is using the email, but you should use the value that corresponds to what you configured in Step 1.
			const { email } = profile;

			const data = await loginWithSiteIdentity( email ); // We'll define this later.

			// If we get user data back from then endpoint, we'll add them to NextAuth's user object.
			if ( data ) {
				user.authToken = data.authToken;
				user.refreshToken = data.refreshToken;
				user.userData = data.user; 
			} else {
				return false;
			}
		} catch ( e ) {
			console.error( e ); // Do something with the error.
			return false;
		}
	},
};
```

Now we need to define the `loginWithSiteIdentity` function that we use in the `signIn` callback. This is where we use the `login` mutation from Headless Login for WPGraphQL to get the user from WordPress.

```jsx
// pages/api/auth/[...nextauth].js

const LOGIN = /* GraphQL */`
  mutation Login($input: LoginInput!) {
    login(input: $input) {
      authToken
      refreshToken
      user {
        ...UserFields
      }
    }
  }
`;

const loginWithSiteIdentity = async ( identity ) => {
	const variables = {
		input: {
			provider: LoginProviderEnum.SiteToken, // 'SITETOKEN',
			identity,
		},
	};

	// We need to pass the Site Token header and secret we defined in the Headless Login settings.
	const headers = {
		[ process.env.SITE_TOKEN_HEADER ]: process.env.SITE_TOKEN_SECRET
	};

	// replace fetchAPI with whatever you're using to connect to WPGraphQL.
	const res = await fetchAPI(
		LOGIN,
		{ variables },
		headers
	);

	if ( res?.errors ) {
		throw new Error( res.errors[ 0 ].message );
	}

	return res?.data?.login;
}
```

As you can see from the above, all that is required to authenticate the user with WordPress is to pass the `Site Token` header and secret, along with the user `identity` we want to match. 

[Despite the risks](#🐲-warning-dragons-ahead), we're trusting NextAuth with the responsibility of authenticating the user, since `signIn` is only called after the user has successfully authenticated with a client we configured.

## 4. Configure the `jwt` callback.

While we have successfully set up NextAuth to authenticate the user with WordPress, we still need to configure it to use WordPress to manage the session.

First we need to configure the `jwt` callback, so it uses the `authToken` and `refreshToken` from WPGraphQL.

```js
const callbacks = {
	signIn: async ( user, account, profile ) => {
		// From step 3.
	},
	/**
	 * The jwt callback is called when a user signs in, or when a session is retrieved from the database.
	 * We use it to set the JWT token and refresh token from WordPress.
	 */
	jwt: async ( {token, user} ) => {
		// If the user is defined, that means we are signing in, so we already have what we need.
		if ( user ) {
			return {
				...token,
				...user,
			}
		}

		// If the user is not defined already, then we want to make sure we're using a fresh auth token. We'll do that by using the refresh token.

		if ( token?.refreshToken ) {
			// if we have a refresh token, we'll try to get a new auth token.
			try {
				const newTokens = await refreshAuthToken( token.refreshToken ); // We'll define this later.

				// If we got a new auth token, we'll update the token.
				if ( newTokens?.authToken ) {
					token.authToken = newTokens.authToken;
				} else {
					// If we didn't that mean's the refresh token is no longer valid, so we'll remove it.
					delete token.authToken;
					delete token.refreshToken;
				}
			}
		} catch {
			// Likewise, if we got an error, we want to remove the refresh token.
			delete token.authToken;
			delete token.refreshToken;
		}

		return token;
	},
};
```

Now we need to define the `refreshAuthToken` function that we use in the `jwt` callback. This is where we use the `refresToken` mutation from Headless Login for WPGraphQL to get a new JWT token.

```js
// pages/api/auth/[...nextauth].js

const refreshAuthToken = async( refreshToken ) => {
	const query = /* GraphQL */`
	mutation RefreshAuthToken($input: RefreshAuthTokenInput!) {
		refreshToken(input: $input) {
			authToken
		}
	}
	`;

	const variables = {
		input: {
			refreshToken,
		},
	};

	// replace fetchAPI with whatever you're using to connect to WPGraphQL.
	const res = await fetchAPI( query, { variables } );

	if ( res?.errors ) {
		throw new Error( res.errors[ 0 ].message );
	}

	return res?.data?.refreshToken;
}
```
## 5. Configure the `session` callback

Now we need to configure the `session` callback to actually use our tokens to manage the local session.

```js
// pages/api/auth/[...nextauth].js

const callbacks = {
	signIn: async ( user, account, profile ) => {
		// From step 3.
	},
	jwt: async ( {token, user} ) => {
		// From step 4.
	},
	/**
	 * The session callback is called when a user signs in, or when a session is retrieved from the database.
	 * We use it to set the session token and refresh token from WordPress.
	 */
	session: async ( {session, token} ) => {

		// If we have an auth token, that means the user is logged in.
		if ( token?.authToken ) {
			session.isLoggedIn = true;
			session.userData = token.userData;
			session.authToken = token.authToken;
			// We don't store the refresh token, since we don't need it in our frontend.
		} else {
			// This means the user is not logged in.
			session.isLoggedIn = false;
			delete session.authToken;
			// We don't delete stale userData, to help the user log back in.
		}

		return session;
	},
};
```

At this point, we have configured NextAuth to authenticate the user with WPGraphQL and to use the WordPress user's JWT tokens to manage the session, and we're ready to start using the session in our frontend.

## 6. Use the `authToken` in your GraphQL requests.

Now that we have NextAuth using our WordPress user's JWT tokens to manage the session, we can use the `authToken` to authenticate our GraphQL requests.

You can do this by using NextAuth's `getSession` function to get the `authToken` from the session, and then passing it to your GraphQL client.

For example: here's the `fetchAPI` function we've been using until now.

```jsx
import { getSession } from 'next-auth/client';

export default async function fetchAPI( query, { variables } = {}, headers = {} ) {
	try {
		// We get the session from NextAuth.
		const session = await getSession();

		// If the user has an authToken, we add it to the headers.
		if ( session?.authToken ) {
			headers.Authorization = `Bearer ${ session.authToken }`;
		}

		const res = await fetch( process.env.WPGRAPHQL_URL, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				Origin: process.env.NEXT_PUBLIC_SITE_URL, // Required because we are restricting domains in WPGraphQL.
				...headers,
			},
			body: JSON.stringify( {
				query,
				variables,
			} ),
		} );

		const json = await res.json();

		if ( json.errors ) {
			console.error( json.errors );
			throw new Error( 'Failed to fetch API' );
		}

		return json.data;
	} catch ( e ) {
		return {
			errors: [ e ],
		}
	}
}
```

The same approach can be taken with Apollo Client, or any other GraphQL client.

## 7. Using the session data in your frontend components.

Using the session data in frontend components follows the traditional NextAuth pattern.

You can use the `useSession` hook to get the session data, and then use the `session` object to conditionally render content.

```jsx
import { useSession } from 'next-auth/client';

const MyComponent = () => {
	const { data } = useSession();

	if ( ! data?.isLoggedIn ) {
		return (<button onClick={ () => signIn() } />)
		>
	}

	// Get whatever you returned from the query.
	return (
		<p>Hello, { data?.userData?.name }</p>

		<button onClick={ () => signOut() } />
	);
}
```


## 8. (Optional) Configure NextAuth to support password authentication.

We can also use NextAuth to support password authentication with our WordPress site.

To support password authentication, we need to configure the the NextAuth [Credentials Provider](https://next-auth.js.org/configuration/providers/credentials) to work with our WPGraphQL `login` mutation.

```jsx
// pages/api/auth/[...nextauth].js

const providers = [
	// ... other providers
	CredentialsProvider( {
		name: 'Password',
		// Defines the fields that will be presented to the user.
		credentials: {
			username: { label: 'Username', type: 'text', placeholder: 'jsmith' },
			password: { label: 'Password', type: 'password' },
		},
		/**
		 * The authorize callback is called when a user signs in with the credentials provider.
		 * We use it to authenticate the user with WordPress.
		 */
		async authorize( credentials ) {
			const user = await loginWithPassword( credentials.username, credentials.password ); // We'll define this later.

			if ( user ) {
				return user;
			}

			// If the user is not found, return null.
			return null;
		}
	} ),
];
```

Now we need to define the `loginWithPassword` function that we use in the `authorize` callback.

This is where we use the `login` mutation from Headless Login for WPGraphQL to get the user from WordPress.

```jsx
// pages/api/auth/[...nextauth].js

const loginWithPassword = async ( username, password ) => {
	const variables = {
		input: {
			provider: LoginProviderEnum.Password, // 'PASSWORD',
			credentials: {
				username,
				password,
			},
		},
	};

	// replace fetchAPI with whatever you're using to connect to WPGraphQL.
	const res = await fetchAPI(
		LOGIN, // This is the same login mutation we used in Step 3.
		{ variables }
	);

	if ( res?.errors ) {
		throw new Error( res.errors[ 0 ].message );
	}

	return res?.data?.login;
}
```

We're _almost_ done. All that's left is to make sure that our `signIn` callback from before doesn't try to authenticate the user with the `login` mutation again. We'll do that by wrapping it in a conditional that checks what NextAuth provider was used to sign in.

```jsx
// pages/api/auth/[...nextauth].js

const callbacks = {
	signIn: async ( user, account, profile ) => {
		// We only want to authenticate the user with WordPress if they used a client-side provider. The 'credentials' provider is already directly authenticating.
		if ( account.provider !== 'credentials' ) {
			// Rest of the callback from step 3.
		}

		return true;
	},
	jwt: async ( {token, user} ) => {
		// From above.
	},
	session: async ( {session, token} ) => {
		// From above.
	},
};
```

And that's it!
