# Example Next.js with API Routes.

(DRAFT - WIP)

1. In the Client settings, set the redirect URI to the [Next.js API route](https://nextjs.org/docs/api-routes/introduction) you want to use.
You can create a different API route for each Login Client, or use a [catch-all route](https://nextjs.org/docs/api-routes/dynamic-api-routes#catch-all-api-routes) to handle all of them (what we'll be doing in this example).

1. The Headless Login plugin generates the Client `authorizationUrl` that you can output straight into your Login component. E.g.

```jsx
// Login.jsx

// replace fetchAPI with whatever you're using to connect to WPGraphQL.
const data = await fetchAPI(
  `query loginClients {
    loginClients {
      clientId
      authorizationUrl
      provider
      name
      ...OtherLoginClientFields
    }
  }
  `
);

return (
  <>
    {
      data?.loginClients.map(
        (client) => (
          <a key={client.clientId} href={client.authorizationUrl}>
            { __( 'Login with: ', 'my-handle' ) + client.name }
          </a>
        )
      )
    }
  </>
)
```

When a user clicks the link, they will be directed to the Authentication provider. Once they authenticate, the Provider will send the authentication response data to the Callback `Redirect URI`.

3. On your Callback API route (e.g. `/pages/api/auth/[provider].jsx` ), you grab the data from the request parameters, and feed it into your WPGraphQL login mutation
```jsx
// pages/api/auth/[provider].jsx

export async function authenticate(provider, code, state) {
  const variables = {
    input: {
      provider,
      oauthResponse: {
        code
      }
    }
  };

  if ( state ) { // Not all providers send a state.
    variables.input.response.state = state;
  }

  // replace fetchAPI with whatever you're using to connect to WPGraphQL.
  const data = await fetchAPI(
    `mutation Login($input: LoginInput!) {
      login(input: $input) {
        authToken
        authTokenExpiration
        refreshToken
        refreshTokenExpiration
        wooSessionToken # If WPGraphQL for Woocommerce is installed.
        user {
          id
          name
          email
          username
          avatar {
            url
          }
          oauth {
            linkedIdentities {
              id
              provider
            }
          }
        }
      }
    }`,
    {
      variables: {
        input: variables.input
      }
    }
  );
    
  return data;
}

const sessionHandler = async ( req, res ) => {
  try {
    const {
      code, // The Authorization code from the Provider
      state, // The State used to validate request authenticity
      provider, // the catch-all param.
    } = await req.query

    // Authenticate the user with WPGraphQL.
    const data = await authenticate( ProviderEnum[provider], code, state );


    // We now have the auth/refresh tokens for a validated WPUser, lets store them.

    const user = {isLoggedIn: true, ...data.login }
    req.session.set( 'user', user );
    await req.session.save();

    // Redirect the user from the api route back to the app.
    res.redirect(307, '/my-profile-page' );
  } catch ( error {
    // Do something if authentication fails.
    const { response: fetchResponse } = error;
    res.status( fetchResponse?.status || 500 ).json( error );
  }
};

// Example: store the session data with 'next-iron-session'.
export function withSession(handler) {
  return withIronSession(handler, { 
    password: process.env.SECRET_COOKIE_PASSWORD,
    cookieName: 'wp-graphql-headless-login-session',
    cookieOptions: {
      // the next line allows to use the session in non-https environments like
      // Next.js dev mode (http://localhost:3000)
      secure: process.env.NODE_ENV === 'production' ? true : false,
    },
  })
}

export default withSession( sessionHandler );
```

4. Next, we need some logic to validate the `authToken`, and if it's expired to request a new one with the `refreshToken`. For that we're going to create a `useUser()` hook that uses `useSWR` on a different API Route (`pages/api/user.jsx`).

```jsx
// lib/hooks/useUser.js
import { useEffect } from 'react'
import Router from 'next/router'
import useSWR from 'swr'

export default function useUser({
  redirectTo = false,
  redirectIfFound = false,
} = {}) {
  const { data: user, mutate: mutateUser } = useSWR('/api/user')

  useEffect(() => {
    // if no redirect needed, just return (example: already on /dashboard)
    // if user data not yet there (fetch in progress, logged in or not) then don't do anything yet
    if (!redirectTo || !user) return

    if (
      // If redirectTo is set, redirect if the user was not found.
      (redirectTo && !redirectIfFound && !user?.isLoggedIn) ||
      // If redirectIfFound is also set, redirect if the user was found
      (redirectIfFound && user?.isLoggedIn)
    ) {
      Router.push(redirectTo)
    }
  }, [user, redirectIfFound, redirectTo])

  return { user, mutateUser }
}
```
And the User API route:

```js
//pages/api/user.js

import jwt from "jsonwebtoken";
import withSession from '../../lib/session' // wrapper for Iron Session from before

function checkExpired(accessToken) {
  const decodedToken = jwt.decode(accessToken)
  // Expiry time is in seconds, but we need milliseconds so we do *1000
  const expiresAt = new Date((decodedToken['exp']) * 1000)
  const now = new Date()
  if (now < expiresAt) {
      //  Not expired
      return false;
  } else {
      //  Expired
      return true;
  }
}

async function refreshAuthToken( refreshToken ) {
  // replace fetchAPI with whatever you're using to connect to WPGraphQL.
  const data = await fetchAPI(
    `mutation Login($refreshToken: String!) {
      refreshToken(input: {refreshToken: $refreshToken}) {
        authToken
        success
      }
    }`,
    {
      variables: {
        refreshToken
      }
    }
  );

  return data?.refreshToken;
}

export default withSession( async ( req, res) =>{
  const user = req.session.get('user')

  if ( user ) {
    if ( checkExpired( user.authToken ) ) {
      // Get new access/auth token
      const newAuthToken = await refreshAuthToken(user.refreshToken).?authToken
      // Remove old access/auth token and store in cookie
      let oldUser = user
      delete oldUser[authToken]
      const newUser = { ...oldUser, [authToken]: newAuthToken }
      await req.session.set('user', newUser)
      await req.session.save()
      // Send back the updated user data
      const savedUser = await req.session.get('user')
      res.send({
        isLoggedIn: true,
        ...savedUser,
      });
    } else {
      // The token is invalid, but we still have the old user data.
      res.send({
        isLoggedIn: false,
        ...user,
      })
    }
  } else {
    // The user wasn't logged in.
    res.send({
      isLoggedIn: false,
    })
  }
} );
```

We can now use our hook in one of our frontend components.
```jsx
// components/Profile.jsx

const Profile = () => {
  const { user } => useUser( {redirectTo: '/login'});

  if (!user || user.isLoggedIn === false) {
    return <Layout>loading...</Layout>
  }

  return (
    <Layout>
      <h1>Your {user?.user?.oauth?.linkedIdentities?.[0]?.provider} profile</h1>
      
      <pre>{JSON.stringify(user, null, 2)}</pre>
    </Layout>
  )
}
```

5. Most importantly, we need to include our new auth tokens on autheticated requests. There's a bunch of different ways to do this, but let's scaffold out that `fetchAPI` function I've be using throughout this example:

```js
// lib/fetchAPI.js

function fetchAPI( query, { variables } = {}, currentUser = {} ) {
  const headers = { 'Content-Type': 'application/json' };

  if( currentUser?.authToken ) {
    headers[
      'Authorization'
    ] = `Bearer ${currentUser.authToken}`
  }

  // If you're using WPGraphQL for Woocommerce, you'll need to add the `woocommerce-session` to the header.
  if( currentUser?.wooSessionToken ) {
    headers[
      'woocommerce-session'
    ] = `Session ${currentUser.wooSessionToken}`
  }

  const res = await fetch( process.env.MY_GRAPHQL_URL, {
    method: 'POST',
    headers,
    body: JSON.stringify({
      query,
      variables
    }),
  });

  const json = await rest.json();
  if( json.errors ) {
    console.error( json.errors );
    throw new Error( 'Failed to fetch API' );
  }

  return json.data;
}
```
