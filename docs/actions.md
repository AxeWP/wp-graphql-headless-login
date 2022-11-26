# Action Hooks

## Activation / Deactivation
### `graphql_login_activate`

Runs when the plugin is activated.

```php
do_action( 'graphql_login_activate' );
```

### `graphql_login_deactivate`

Runs when the plugin is deactivated.

```php
do_action( 'graphql_login_deactivate' );
```

### `graphql_login_delete_data`

Runs after the plugin deletes its data on deactivate.

```php
do_action( 'graphql_login_delete_data' );
```

## Lifecycle
### `graphql_login_init`

Runs when the plugin is initialized.

```php
do_action( 'graphql_login_init', $instance );
```

#### Parameters

* **`$instance`** _(WPGraphQL\Login\Main)_ : The instance of the plugin.

### `graphql_login_before_register_types`

Runs before the plugin registers any GraphQL types to the schema.

```php
do_action( 'graphql_login_before_register_types' );
```

### `graphql_login_after_register_types`

Runs after the plugin finishes registering all GraphQL types to the schema.

```php
do_action( 'graphql_login_after_register_types' );
```

### `graphql_login_after_provider_init`

Fires after a Login Provider has been initialized.

```php
do_action( 'graphql_login_after_provider_init', $slug, $provider_config );
```

#### Parameters

* **`$slug`** _(string)_ : The provider slug.
* **`$provider_config`** _(WPGraphQL\Login\Auth\ProviderConfig\ProviderConfig)_ : The instance of the ProviderConfig.

### `graphql_login_after_client_init`

Fires after a Login Client has been initialized.

```php
do_action( 'graphql_login_client_init', $slug, $settings, $provider_config, $client );
```

#### Parameters

* **`$slug`** _(string)_ : The provider slug.
* **`$settings`** _(array)_ : The client settings.
* **`$provider_config`** _(WPGraphQL\Login\Auth\ProviderConfig\ProviderConfig)_ : The instance of the ProviderConfig.
* **`$client`** _(WPGraphQL\Login\Auth\Client)_ : The instance of the Client.

### `graphql_login_before_password_authenticate`

Fires before the user is authenticated with a password.

```php
do_action( 'graphql_login_before_password_authenticate', $input );
```

#### Parameters

* **`$input`** _(array)_ : The input data.
	* **`$input['username']`** _(string)_ : The username.
	* **`$input['password']`** _(string)_ : The password.

### `graphql_login_before_authenticate`

Fires before the user is authenticated.

```php
do_action( 'graphql_login_before_authenticate', $slug, $input, $settings, $provider_config, $client );
```

#### Parameters

* **`$slug`** _(string)_ : The provider slug.
* **`$input`** _(array)_ : The mutation input data.
* **`$settings`** _(array)_ : The client settings.
* **`$provider_config`** _(WPGraphQL\Login\Auth\ProviderConfig\ProviderConfig)_ : The instance of the ProviderConfig.
* **`$client`** _(WPGraphQL\Login\Auth\Client)_ : The instance of the Client.

### `graphql_login_validate_client`

Fires when validating the client instance.

```php
do_action( 'graphql_login_validate_client', $client );
```

#### Parameters

* **`$client`** _(WPGraphQL\Login\Auth\Client)_ : The instance of the Client.

### `graphql_login_after_successful_password_login`

Fires after the user is authenticated with a password.

```php
do_action( 'graphql_login_after_successful_password_login', $payload );
```

#### Parameters

* **`$payload`** _(array)_ : The payload data.
	* **`$payload['authToken']`** _(string)_ : The authentication token.
	* **`$payload['authTokenExpiration']`** _(int) : The authentication token expiration timestamp.
	* **`$payload['refreshToken']`** _(string)_ : The refresh token.
	* **`$payload['refreshTokenExpiration']`** _(int) : The refresh token expiration timestamp.
	* **`$user`** _(WP_User)_ : The authenticated user.

### `graphql_login_after_successful_login`

Fires after the user is successfully logged in.

```php
do_action( 'graphql_login_after_successful_login', $payload, $user_data, $client );
```

#### Parameters

* **`$payload`** _(array)_ : The payload data.
	* **`$payload['authToken']`** _(string)_ : The user's Auth Token.
	* **`$payload['authTokenExpiration']`** _(int)_ : The expiration timestamp of the Auth Token.
	* **`$payload['refreshToken']`** _(string)_ : The user's Refresh Token.
	* **`$payload['refreshTokenExpiration']`** _(int)_ : The expiration timestamp of the Refresh Token.
	* **`$payload['user']`** _(WP_User)_ : The user object.

### `graphql_login_link_user_identity`

Fires when linking a user identity.

```php
do_action( 'graphql_login_link_user_identity', $linked_user, $user_data, $client );
```

#### Parameters

* **`$linked_user`** _(WP_User|false)_ : The user object. False if the identity could not be linked.
* **`$user_data`** _(array)_ : The user data from the Authentication provider.
* **`$client`** _(WPGraphQL\Login\Auth\Client)_ : The instance of the Client.
