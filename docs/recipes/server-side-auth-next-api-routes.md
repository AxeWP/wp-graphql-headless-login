# Recipe: Server-side Authentication flow with Next.JS API Routes.

This example shows how to use Headless Login for WPGraphQL to authenticate users on the server-side using one the built-in authentication providers. We'll show examples for OAuth2 and Password authentication.

We'll be using [`iron-session`](https://github.com/vvo/iron-session) to store the user's session data, but you can use any session management library you like.

## 1. Configure the Headless Login providers.

For more information on configuring the providers, see the [Settings Guide](..reference/settings.md).

> Note: OAuth2 providers require a 'Redirect URI', which we will be creating as a [Next.js API route](https://nextjs.org/docs/api-routes/introduction) in [Step 3](#3-create-the-authentication-api-route).

You can create a different API route for each Login Client, or use a [catch-all route](https://nextjs.org/docs/api-routes/dynamic-api-routes#catch-all-api-routes) to handle all of them (what we'll be doing in this example).

## 2. Create the Login component.

In your headless app, you will need to create a Login component that sends the user to authenticate with the provider. 

### 2A. OAuth2 authentication

OAuth2 providers require a `Redirect URI` to be configured. This is the URL that the provider will send the authentication response data to.

You can choose to create the necessary `authorizationUrl` yourself, or use the one generated by the plugin for a DRYer solution.

E.g.

```js
// Login.js

// replace fetchAPI with whatever you're using to connect to WPGraphQL.
const data = await fetchAPI(
  `query LoginClients {
    loginClients {
      authorizationUrl
      name
      ...OtherLoginClientFields
    }
  }
  `
);

// Let's filter out the Password provider, since we'll be using a different method for that.
data.loginClients = data.loginClients.filter(
	(client) => client.name !== 'Password'
);

return (
  <>
    {
      data?.loginClients.map(
        (client) => (
          <a key={client.name} href={client.authorizationUrl}>
            { __( 'Login with: ', 'my-handle' ) + client.name }
          </a>
        )
      )
    }
  </>
);
```

When a user clicks the link, they will be directed to the Authentication provider. Once they authenticate, the Provider will send the authentication response data to the Callback `Redirect URI`, which will be sent to WPGraphQL by our Authentication API route.

### 2B. Password authentication.

For Password authentication, we need to create a LoginForm component that sends the user's credentials to our Authentication API route.

```js
// LoginForm.js

const [username, setUsername] = useState('');
const [password, setPassword] = useState('');

const { login, loading, error } = usePasswordLogin(); // We'll define this hook later.

return (
	<form
		onSubmit={ (e) => {
			e.preventDefault();

			// We'll define this later, but for now its enough to know it takes the username, password, and redirect URL and processes it via our Authentication API route.
			login(username, password, '/dashboard');
		} }
	>
		<input
			type="text"
			name="username"
			placeholder="Username"
			value={username}
			onChange={setUsername}
		/>
		<input
			type="password"
			name="password"
			placeholder="Password"
			value={password}
			onChange={setPassword}
		/>
		<button type="submit">Login</button>
	</form>
);
```

## 3. Create the Authentication API route.

The Authentication API route is where we process the authentication data and send it to WPGraphQL. We're using an API route to prevent the authentication data from being exposed to the client, but you can also use middleware or a serverless function to do this.

To keep our code DRY, we'll breaking our code into a few reusable parts, that we'll then use in the provider-specific API routes.

### 3A. The `authenticate` function.

This function takes the authentication data from the provider, and sends it to WPGraphQL. It returns the user's authentication data, which we'll use to create the user's session.

```js
// lib/auth/authenticate.js

async function authenticate( input ) {
	const query = `
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

	// replace fetchAPI with whatever you're using to connect to WPGraphQL.
	const res = await fetchAPI( query, { input } );

	if( res?.errors ) {
		throw new Error( res.errors[0].message );
	}

	return res?.data?.login;
}
```

### 3B. The `sessionHandler` function.

This function takes the authentication data from the provider, and creates the user's session.

```js
// lib/auth/sessionHandler.js

export async function sessionHandler( req, res input ) {
	try {
		const data = await authenticate( input);

		// We're using iron session to store the session data in a secure httpOnly cookie, but you can use any session management library you like.
		const session = await getIronSession( req, res, ironOptions );
		const user = {
			...data,
			isLoggedIn: true,
		};

		session.user = user;
		await session.save();

		// Let's send them somewhere.
		return res.redirect(307, '/dashboard');
	} catch (e) {
    // Do something with the error
    res.status(401).json({ error: e.message });

    // Or redirect them to the login page.
    return res.redirect(401, '/login');
	}
}

// And some more iron-session stuff:
export const ironOptions = {
  cookieName: 'wp-graphql-headless-login-session',
  password: process.env.SECRET_COOKIE_PASSWORD,
  cookieOptions: {
    // the next line allows to use the session in non-https environments like
    // Next.js dev mode (http://localhost:3000)
    secure: process.env.NODE_ENV === 'production',
  },
};
```

### 3C. The provider-specific API routes.

Now that we have our `authenticate` and `sessionHandler` functions, we can create our provider-specific API routes.

For this example, we're going to use a Catch-All route  (e.g. `/pages/api/auth/[provider].js` ), but you can also use a separate route for individual providers that have differing logic (e.g. password authentication).

```js
// pages/api/auth/[provider].js

// A simple helper function to get the provider-specific input for the mutation.
async function getProviderInput( provider, req ) {
	switch ( provider ) {
		case 'password':
			return {
				provider,
				credentials: {
					username: req.body.username,
					password: req.body.password,
				},
			};
		default:
			const providerEnum = provider.toUpperCase(); // IRL use the generated enum type, e.g. LoginProviderEnum[provider].

			const input = {
				provider: providerEnum,
				oauthResponse: {
					code: req.query.code,
				},
			}

			if ( req.query.state ) { // Not all providers send a state.
				input.oauthResponse.state = req.query.state;
			}

			return input;
	}
}

async function handler(req, res) {
	const { provider } = req.query;

	const input = await getProviderInput(provider, req);

	return sessionHandler(req, res, input);
}

// This is an iron-session thing.
export default withIronSessionApiRoute(sessionHandler, ironOptions);
```

## 4. Create the Logout API route.

On your Logout API route (e.g. `/pages/api/logout.js` ), you can clear the session data.

Since we're using `iron-session`, we can just call `req.session.destroy()`. If you are useing a different session management library or your own secure cookie implementation, you'll need to use that library's API to clear the session data.

```js

// pages/api/auth/logout.js

async function logoutHandler(req, res) {
  req.session.destroy();

  // Let's send back some JSON.
  return res.status(200).json({ isLoggedIn: false});
}

export default withIronSessionApiRoute(logoutHandler, ironOptions);
```

## 5. Create the Token Validation API route.

Headless Login uses JWT tokens for authentication. These tokens have an expiration time, and you will need to refresh them before they expire.

We can handle validating and refreshing the token on the server-side, so we don't expose these tokens to the client.

> Note: `req.session` is made available by `iron-session`. If you're using a different session management library, you'll need to use that library's API to access the session data.

```js
// pages/api/auth/user.js

// We'll use this function in our handler, to check if the authToken has expired.
function isTokenExpired( token ) : boolean {
  const decodedToken = decode( token );

  if ( ! decodedToken?.exp ) {
    return false;
  }

  // Expiry time is in seconds, but we need milliseconds so we do *1000
  const expiresAt = new Date( ( decodedToken.exp ) * 1000 );
  const now = new Date();

  return now >= expiresAt;
}

// Our refresh token call to WPGraphQL.
async function refreshAuthToken( refreshToken ) {
  const variables = {
    refreshToken,
  };

  // replace fetchAPI with whatever you're using to connect to WPGraphQL.
  const res = await fetchAPI(
    `mutation RefreshToken( $refreshToken: String! ) {
      refreshToken(
        input: {refreshToken: $refreshToken }
      ) {
        authToken
        refreshToken
        success
      }
    }`,
    {
      variables,
    },
  );

  if ( res?.errors ) {
    throw new Error( res?.errors[ 0 ].message );
  }

  return res?.data?.refreshToken;
}

async function userHandler(req, res) {
  const user = req.session?.user;

  // If the user doesn't have a refrsh token, they're not logged in.
  if ( ! user?.refreshToken ) {
    req.session.user = {
      ...user,
      isLoggedIn: false,
    };

    await req.session.save();
    return res.status( 401 ).send( req.session.user );
  }

  // If the user doesn't have an authToken, or it's expired, we'll refresh it.
  if ( ! user?.authToken || isTokenExpired( user.authToken ) ) {
    try {
      const { authToken, refreshToken, success } = await refreshTokens(
        user.refreshToken
      );

      user.authToken = authToken;
      user.refreshToken = refreshToken;

      req.session.user = {
        ...user,
        isLoggedIn: success,
      };
      
      await req.session.save();
      return res.status( success ? 200 : 401 ).send( req.session.user );
    } catch {
      // The token is invalid, but we have the stale data.
      req.session.user = {
        ...user,
        isLoggedIn: false,
      };

      await req.session.save();

      return res.status( 401 ).send( req.session.user );
    }
  }

  // If we get here, the user is logged in.
  return res.status( 200 ).send( req.session.user );
}

export default withIronSessionApiRoute(userHandler, ironOptions);
```

## 6. Use the authToken in your GraphQL requests.

Now that we have a way to authenticate with WPGraphQL, we can use the `authToken` in our GraphQL requests.

You can do this by fetching the `authToken` from the session data, and passing it in the `Authorization` header.

For example: here's the `fetchAPI` function we've been using until now.

```jsx
// utils/fetchAPI.js

export default async function fetchAPI(query, { variables } = {}) {
  const currentUser = await fetch('/api/auth/user').then(res => res.json());

  const headers = { 'Content-Type': 'application/json' };

  if( currentUser?.authToken ) {
    headers.Authorization = `Bearer ${currentUser.authToken}`;
  }

  try {
    const res = await fetch(process.env.WPGRAPHQL_URL, {
      method: 'POST',
      headers,
      body: JSON.stringify({
        query,
        variables,
      }),
    });

    const json = await res.json();

    if (json.errors) {
      console.error(json.errors);
      throw new Error('Failed to fetch API');
    }

    return json.data;

  } catch (e) {
    return {
      errors: [ e ],
    }
  }
}
```

The same approach can be taken with Apollo Client, or any other GraphQL client.

## 7. (Optional) Create some custom hooks.

We can create custom hooks to make it easier to handle authentication flows.

Here are a few common examples.

> Note: We are using the fetch API but you can use 'swr', or any other library you prefer.

### `useAuth`

This example hook will return whether the user is authenticated, and possibly redirect them to a specific page.

```jsx
// hooks/useAuth.js

export function useAuth( {
  redirectTo = false, // An optional URL to redirect to.
  redirectIfFound = false, // If true, redirect if the user is already logged in.
} ) {
  const [ isAuthenticated, setIsAuthenticated ] = useState( false );
  const [ isReady, setIsReady ] = useState( undefined );

  useEffect( () => {
    ( async () => {
      const res = await fetch( '/api/auth/user', {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
        },
      } );

      const user = await res.json();
      setIsAuthenticated( user?.isLoggedIn === true );
      setIsReady( true );
    } )();
  }, [] );

  useEffect( () => {
    if ( ! isReady || ! redirectUrl ) {
      return;
    }

    if (
      // If redirectIfFound is also set, redirect if the user was found
      ( redirectIfFound && isAuthenticated ) || ( ! redirectIfFound && ! isAuthenticated ) ) {
      setTimeout( () => {
        window.location.assign( redirectUrl );
      }, 200 );
    }
  }, [ isReady, isAuthenticated, redirectUrl, redirectIfFound ] );

  return { isReady, isAuthenticated };
}
```

### `useLogout`

This example hook will log the user out, and optionally redirect them to a specific page upon successful logout.

```jsx
// hooks/useLogout.js

export function useLogout() {
  const [ error, setError ] = useState( undefined );
  const [ loading, setLoading ] = useState( false );

  async function logout( redirectUrl: string ) {
    setLoading( true );

    const logoutUrl = `/api/auth/logout`;

    const res = await fetch( logoutUrl, {
      method: 'POST',
    } );

    if ( ! res.ok ) {
      setError( res );
      setLoading( false );
      return;
    }

    if ( redirectUrl ) {
      window.location.assign( redirectUrl );
    } else {
      window.location.reload();
    }
    setLoading( false );
  }

  return {
    error,
    logout,
    loading,
  };
}
```

### `usePasswordLogin`

This example hook will log the user in with a username and password, and optionally redirect them to a specific page upon successful login.

This is the hook we used in our [example Login Form component above](#2B-Password-Authentication).

```jsx
// hooks/usePasswordLogin.js

export function usePasswordLogin() {
	const [ error, setError ] = useState( undefined );
	const [ loading, setLoading ] = useState( false );
	// We use a local copy, since we don't want to update the session data until the login is successful.
	const [ data, setData ] = useState( undefined );
	const [ loginRedirectUrl, setLoginRedirectUrl ] = useState( undefined );

	/**
	 * A function to log the user in.
	 * @param {string} username The username to log in with.
	 * @param {string} password The password to log in with.
	 * @param {string} redirectUrl An optional URL to redirect to after login.
	 */
	async function login( username, password, redirectUrl ) {
		// Clear old states.
		setError( undefined );
		setData( undefined );
		setLoading( true );
		setLoginRedirectUrl( redirectUrl );

		const loginUrl = `/api/auth/password`; // This is the route we created in step 3.

		const res = await fetch( loginUrl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
			},
			body: JSON.stringify( {
				username,
				password,
			} ),
		} );

		const data = await res.json();

		if ( ! res.ok ) {
			setError( data );
			setLoading( false );
			return;
		}

		setData( data );

		// If we get here, the login was successful, so let's redirect.
		if ( loginRedirectURL ) {
			window.location.assign( loginRedirectUrl );
		}
		setLoading( false );
	}

	return {
		error,
		login,
		loading,
		data,
	};
}
```