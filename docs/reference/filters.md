# Filter Hooks

## Table of Contents

* [GraphQL Type Registration](#graphql-type-registration)
	* [`graphql_login_registered_{type}_classes`](#graphql_login_registered_type_classes)
* [Authentication](#authentication)
	* [`graphql_login_auth_get_user`](#graphql_login_auth_get_user)
	* [`graphql_login_payload`](#graphql_login_payload)
* [Secrets & Tokens](#secrets--tokens)
	* [`graphql_login_jwt_secret_key`](#graphql_login_jwt_secret_key)
	* [`graphql_login_refresh_token_validity`](#graphql_login_refresh_token_validity)
	* [`graphql_login_refresh_token_expiration_timestamp`](#graphql_login_refresh_token_expiration_timestamp)
	* [`graphql_login_user_secret`](#graphql_login_user_secret)
	* [`graphql_login_iss_allowed_domains`](#graphql_login_iss_allowed_domains)
	* [`graphql_login_edit_jwt_capability`](#graphql_login_edit_jwt_capability)
	* [`graphql_login_token_not_before_timestamp`](#graphql_login_token_not_before_timestamp)
	* [`graphql_login_token_expiration_timestamp`](#graphql_login_token_expiration_timestamp)
	* [`graphql_login_token_before_sign`](#graphql_login_token_before_sign)
	* [`graphql_login_signed_token`](#graphql_login_signed_token)
	* [`graphql_login_token_validity`](#graphql_login_token_validity)
* [Authorization Headers](#authorization-headers)
	* [`graphql_login_get_auth_header`](#graphql_login_get_auth_header)
	* [`graphql_login_refresh_header`](#graphql_login_refresh_header)
* [Client & Provider Configuration](#client--provider-configuration)
	* [`graphql_login_registered_provider_configs`](#graphql_login_registered_provider_configs)
	* [`graphql_login_provider_config_instances`](#graphql_login_client_settings)
	* [`graphql_login_client_options_fields](#graphql_login_client_options_fields)
	* [`graphql_login_client_options_schema`](#graphql_login_client_options_schema)
	* [`graphql_login_setting`](#graphql_login_setting)
	* [`graphql_login_access_control_settings`](#graphql_login_access_control_settings)
	* [`graphql_login_provider_settings`](#graphql_login_provider_settings)
	* [`graphql_login_login_options_fields`](#graphql_login_login_options_fields)
	* [`graphql_login_login_options_schema`](#graphql_login_login_options_schema)
	* [`graphql_login_client_options`](#graphql_login_client_options)
	* [`graphql_login_user_types](#graphql_login_user_types)
	* [`graphql_login_mapped_user_data`](#graphql_login_mapped_user_data)

## GraphQL Type Registration

### `graphql_login_registered_{type}_classes`

Filters the list of classes that are registered as GraphQL Types.

Possible `type` values are `connection`, `enum`, `field`, `input`, `interface`, `mutation` and `object`.

```php
apply_filters( 'graphql_login_registered_connection_classes', $classes );
apply_filters( 'graphql_login_registered_enum_classes', $classes );
apply_filters( 'graphql_login_registered_field_classes', $classes );
apply_filters( 'graphql_login_registered_input_classes', $classes );
apply_filters( 'graphql_login_registered_interface_classes', $classes );
apply_filters( 'graphql_login_registered_mutation_classes', $classes );
apply_filters( 'graphql_login_registered_object_classes', $classes );
```

#### Parameters

* **`$classes`** _(array)_ : The list of PHP classes that are registered as GraphQL Types. These classes must extend the `WPGraphQL\Login\Vendor\AxeWP\GraphQL\Interfaces\GraphQLType` interface.

## Authentication

### `graphql_login_auth_get_user`

Filter to transform the user data returned from `ProviderConfig::authenticate_and_get_user_data()` to a `WP_User` object.

Useful for adding support for custom authentication provider types.

```php
apply_filters( 'graphql_login_auth_get_user', $provider_type, $user_data, $client );
```

#### Parameters

* **`$provider_type`** _(_string)_ : The provider type.
* **`$user_data`** _(array)_ : The user data returned from the authentication provider.
* **`$client`** _(WPGraphQL\Login\Auth\Client)_ : The authentication client.

### `graphql_login_payload`

Filters the Login mutation payload before returning.

```php
apply_filters( 'graphql_login_payload', $payload, $user, $user_data, $client );
```

#### Parameters

* **`$payload`** _(array)_ : The Login mutation payload.
* **`$user`** _(WP_User)_ : The authenticated user.
* **`$user_data`** _(array)_ : The user data returned from the authentication provider.
* **`$client`** _(WPGraphQL\Login\Auth\Client)_ : The authentication client.

## Secrets & Tokens

### `graphql_login_jwt_secret_key`

Filter the secret key used to sign the JWT auth and refresh tokens.

```php
apply_filters( 'graphql_login_jwt_secret_key', $secret );
```

#### Parameters

* **`$secret`** _(string)_ : The secret key.

### `graphql_login_refresh_token_validity`

Filters the duration for which a Refresh Token should be considered valid.

```php
apply_filters( 'graphql_login_refresh_token_validity', $validity );
```

#### Parameters

* **`$validity`** _(int)_ : The validity in seconds. Defaults to 1 year.

### `graphql_login_refresh_token_expiration_timestamp`

Filters the Refresh Token expiration timestamp for all users.

```php
apply_filters( 'graphql_login_refresh_token_expiration_timestamp', $timestamp );
```

#### Parameters

* **`$timestamp`** _(int)_ : The expiration timestamp.

### `graphql_login_user_secret`

Filter the user secret before returning it, allowing for individual systems to override what's returned.

```php
apply_filters( 'graphql_login_user_secret', $secret, $user_id );
```

#### Parameters

* **`$secret`** _(string|WP_Error)_ : The user secret.
* **`$user_id`** _(int)_ : The user ID.

### `graphql_login_iss_allowed_domains`

Filter the allowed domains for the token. This is useful if you want to make your token valid over several domains.

```php
apply_filters( 'graphql_login_iss_allowed_domains', $allowed_domains );
```

#### Parameters

* **`$allowed_domains`** _(string[])_ : An array of allowed domains.

### `graphql_login_edit_jwt_capability`

Filter the capability that is tied to editing/viewing user OAuth info.

```php
apply_filters( 'graphql_login_edit_jwt_capability', $capability );
```

#### Parameters

* **`$capability`** _(string)_ : The user capability. Defaults to `'edit_users'`.

### `graphql_login_token_not_before_timestamp`

Determines the "not before" value for the user's auth and refresh tokens.

```php
apply_filters( 'graphql_login_token_not_before_timestamp', $issued, $user );
```

#### Parameters

* **`$issued`** _(int)_ : The timestamp of the authentication used in the token`.
* **`$user`** _(WP_User)_ : The authenticated user.

### `graphql_login_token_expiration_timestamp`

Determines the "not before" value for the user's auth and refresh tokens.

```php
apply_filters( 'graphql_login_token_expiration_timestamp', $issued, $user );
```

#### Parameters

* **`$issued`** _(int)_ : The timestamp of the authentication used in the token`.
* **`$user`** _(WP_User)_ : The authenticated user.

### `graphql_login_token_before_sign`

Filters the token before it is signed, allowing for individual systems to configure the token as needed.

```php
apply_filters( 'graphql_login_token_before_sign', $token, $user );
```

#### Parameters

* **`$token`** _(array)_ : The token array that will be encoded.
* **`$user`** _(WP_User)_ : The authenticated user.

### `graphql_login_signed_token`

Filter the token before returning it, allowing for individual systems to override what's returned.
For example, if the user should not be granted a token for whatever reason, a filter could have the token return null.

```php
apply_filters( 'graphql_login_signed_token', $token, $user_id );
```

#### Parameters

* **`$token`** _(string)_ : The signed JWT token that will be returned.
* **`$user_id`** _(int)_ : The ID of the user the JWT is associated with.

### `graphql_login_token_validity`

Filter the validity length for the token. Defaults to 300 seconds.

```php
apply_filters( 'graphql_login_token_validity', $validity );
```

#### Parameters

* **`$validity`** _(int)_ : The validity length (in seconds)

## Authorization Headers

### `graphql_login_get_auth_header`

Filters the Authorization header before it is used to authenticate the user's HTTP request.

```php
apply_filters( 'graphql_login_auth_header', $auth_header );
```

#### Parameters

* **`$auth_header`** _(string)_ : The header used to authenticate a user's HTTP request.

### `graphql_login_refresh_header`

Filters the Refresh Authorization header before it is used to authenticate the user's HTTP request.

```php
apply_filters( 'graphql_login_refresh_header', $refresh_header );
```

#### Parameters

* **`$refresh_header`** _(string)_ : The refresh header.

## Client & Provider Configuration

### `graphql_login_registered_provider_configs`

Filters the registered provider configurations.
Useful for removing a built-in provider, or for adding a custom one.

```php
apply_filters( 'graphql_login_registered_provider_configs', $provider_configs);
```

#### Parameters

* **`$provider_configs`** _(array)_ : The registered `ProviderConfig` classes, keyed to their slug.

### `graphql_login_provider_config_instances`

Filters the list of enabled ProviderConfig instances.

```php
apply_filters( `graphql_login_provider_config_instances', $provider_configs );
```

#### Parameters

* **`$provider_configs`** _(WPGQL\Login\Auth\ProviderConfig\ProviderConfig[])_ : The list of enabled ProviderConfig instances.

### `graphql_login_client_options_fields`

Filters the GraphQL fields for the provider's Client Options.

```php
apply_filters( 'graphql_login_client_options_fields', $fields, $slug );
apply_filters( 'graphql_login_{$slug}_client_options_fields', $fields );
```

#### Parameters

* **`$fields`** _(array)_ : An array of WPGraphQL field $configs.
* **`$slug`** _(string)_ : The Authentication provider slug.

### `graphql_login_client_options_schema`

Filters the WP REST schema for the provider's Client Options settings. Useful for modifying Client Options displayed in the admin.

```php
apply_filters( 'graphql_login_client_options_schema', $settings, $slug );
apply_filters( 'graphql_login_{$slug}_client_options_fields', $settings );
```

#### Parameters

* **`$settings`** _(array)_ : The WP REST [schema config](https://developer.wordpress.org/rest-api/extending-the-rest-api/schema), with the addition of the 'help' and 'required' key/values used when displaying the settings in the Admin. 
* **`$slug`** _(string)_ : The Authentication provider slug.

### `graphql_login_setting`

Filters the value of a setting.

```php
apply_filters( 'graphql_login_setting', $value, $option_name, $default );
```

#### Parameters

* **`$value`** _(mixed)_ : The value of the setting.
* **`$option_name`** _(string)_ : The name of the setting. In the database, this is prefixed with `wpgraphql_login_settings`
* **`$default`** _(mixed)_ : The default value of the setting.

### `graphql_login_access_control_settings`

Filters the settings for a provider.

```php
apply_filters( 'graphql_login_access_control_settings', $settings, $slug );
```

#### Parameters

* **`$settings`** _(array)_ : The access control settings.
* **`$default`** _(string)_ : The default value if none is set.

### `graphql_login_provider_settings`

Filters the settings for a provider.

```php
apply_filters( 'graphql_login_provider_settings', $settings, $slug );
```

#### Parameters

* **`$settings`** _(array)_ : The settings for the provider.
* **`$slug`** _(string)_ : The provider slug.

### `graphql_login_login_options_fields`

Filters the GraphQL fields for the provider's Login Options.

```php
apply_filters( 'graphql_login_login_options_fields', $fields, $slug );
apply_filters( 'graphql_login_{$slug}_login_options_fields', $fields );
```

#### Parameters

* **`$fields`** _(array)_ : An array of WPGraphQL field $configs.
* **`$slug`** _(string)_ : The Authentication provider slug.

### `graphql_login_login_options_schema`

Filters the WP REST schema for the provider's Client Options settings. Useful for modifying Client Options displayed in the admin.

```php
apply_filters( 'graphql_login_login_options_schema', $settings, $slug );
apply_filters( 'graphql_login_{$slug}_login_options_fields', $settings );
```

#### Parameters

* **`$settings`** _(array)_ : The WP REST [schema config](https://developer.wordpress.org/rest-api/extending-the-rest-api/schema), with the addition of the 'help' and 'required' key/values used when displaying the settings in the Admin. 
* **`$slug`** _(string)_ : The Authentication provider slug.

### `graphql_login_client_options`

Filters the options used to configure the OAuth2 provider instance within the ProviderConfig.

```php
apply_filters( 'graphql_login_client_options', $options, $slug );
```

#### Parameters

* **`$options`** _(array)_ : The provider options stored in the database.
* **`$slug`** _(string)_ : The authentication provider slug.

### `graphql_login_user_types`

Filters the GraphQL `User` types which should have the `auth: AuthenticationData` field added to them.

```php
apply_filters( 'graphql_login_user_types', $type_names);
```

#### Parameters

* **`$type_names`** _(string[])_ : The names of the GraphQL 'user' types. Defaults to 'User'


### `graphql_login_mapped_user_data`

Filters the user data mapped from the authentication rovider before creating the user.
Useful for mapping custom fields from the provider to the WP_User.

```php
apply_filters( 'graphql_login_mapped_user_data', $user_data, $client );
```

#### Parameters

* **`$user_data`** _(array)_ : The WP User data.
* **`$client`** _(\WPGraphQL\Login\Auth\ProviderConfig/ProviderConfig)_ : The ProviderConfig instance.

## Reference

- [Actions](/docs/reference/actions.md)
- [Filters ( 🎯 You are here )](/docs/reference/filters.md)
- [Javascript API](/docs/reference/javascript-api.md)
- [Mutations](/docs/reference/mutations.md)
- [Queries](/docs/reference/queries.md)
- [Settings](/docs/reference/settings.md)
