# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to a modified version of [Semantic Versioning](./README.md#updating-and-versioning).

## [Unreleased]

## [0.3.1] - 2024-09-07

This _patch_ releases fixes a bug where descriptions were not being displayed for `FormTokenField` types in the settings screen. Additionally, we've updated our dependencies to the latest (SemVer-compatibile) versions.

- fix: Display missing help text to `FormTokenField` types in the settings screen. H/t alexookah.
- dev: Add `react-jsx-runtime` polyfill for WordPress backwards-compatibility.
- chore: Update Strauss to v0.19.1.
- chore: Update Composer dependencies.
- chore: Update NPM dependencies.
- tests: Fix test helper PSR-4 namespaces.
- ci: Replace `docker-compose` commands with `docker compose`.
- ci: Test compatibility with WordPress 6.6.1.

## [0.3.0] - 2024-04-06

This _major_ release refactors the root files to use the `WPGraphQL\Login` namespace. It also exposes the `authTokenExpiration` field in the `refreshToken` mutation response, improves code quality, adds explicit support for WordPress 6.5.0, and more.

> [!NOTE]
> Although this release technically contains breaking changes, these changes are limited to developers directly extending the `wp-graphql-headless-login.php`, `wp-graphql-activation.php`, `wp-graphql-deactivation.php` files, and the `WPGraphQL\Login\Main` class.
> If you are using the plugin as intended, you should not experience any issues when upgrading.

- feat: Add the `authTokenExpiration` field to the `refreshToken` mutation response. H/t @richardaubin.
- chore!: Add `WPGraphQL/RankMath` namespace to root-level files ( `activation.php`, `deactivation.php`, `wp-graphql-rank-math.php` ).
- chore: Declare `strict_types` in all PHP files.
- chore: Update Composer dev-deps and fix newly-surfaced PHPCS smells.
- chore: Lock WPBrowser to v3.5.x to prevent conflicts with Codeception.
- chore: Implement PHPStan strict rules and fix type errors.
- chore: Update WPGraphQL Plugin Boilerplate to v0.1.0.
- ci: Update GitHub Actions to latest versions.
- ci: Test plugin compatibility with WordPress 6.5.0.
- ci: Update Strauss to v0.17.0

## [0.2.0] - 2024-02-04

This _major_ release bumps the minimum supported WordPress version to 6.0, and the minimum supported WPGraphQL version to 1.14.0. It also fixes a bug when extending the `OAuth2Config` class`.

Note: This release is a breaking change, as the `Psr` dependencies are now prefixed with `\WPGraphQL\Login\Vendor`. This class should only be used internally, but if for some reason you're relying on the bundled `Psr` classes in your own code, you'll need to update your references.

- fix: Avoid strict-typing `League\OAuth2\Client\Provider\AbstractProvider` to the `\WPGraphQL\Login\Vendor` namespace. H/t @pat-flew .
- chore!: Use Strauss to prefix `Psr` dependencies. This is a breaking change, as the `Psr` dependencies are now prefixed with `\WPGraphQL\Login\Vendor`.
- chore!: Bump minimum supported WordPress version to 6.0.
- chore!: Bump minimum supported WPGraphQL version to 1.14.0.
- chore: Refactor `Autoloader` class for DRYness.
- chore: Update `axepress/wp-graphql-cs` to latest version and lint.
- chore: Update Composer dependencies to latest.
- chore: Update NPM dependencies for WordPress 6.0+.
- chore: Cleanup CI configuration files.
- ci: Test plugin against WordPress 6.4.3.

## [0.1.4] - 2023-10-15

This minor release fixes a bug where the `determine_current_user` filter was being applied too late in the lifecycle for some plugins. It also better integrates with WPGraphQL for WooCommerce upcoming release which adds built-in support for the plugin. We've also upgraded our coding standards and fixed all the resulting issues.

- fix: Apply the `determine_current_user` filter before the plugin is initialized. H/t @kidunot89 for reporting.
- dev: Refactor autoload handling to `WPGraphQL\Login\Autoloader` class. Note: this does *not* remove the `vendor/` or `vendor-prefixed/` directories from the repository.
- dev: Remove local registration of `LoginPayload.customer` for WooGraphQL 0.18.2+. Props @kidunot89.
- dev: Deprecate `LoginPayload.wooSessionToken` in favor of `loginPayload.sessionToken` (added in WooGraphQL 0.18.2+).
- chore: Update Composer dependencies.
- chore: Update WPGraphQL Coding Standards to v2.0.0-beta and lint.

## [0.1.3] - 2023-8-20

This release includes a refactored Admin JS package for backwards-compatibility and stability, new features and developer hooks extend and customize the authentication lifecycle, and a handful of bug fixes.

- feat: Add support for setting a custom `scopeSeparator` for Generic OAuth2 providers. H/t @martinowren for bringing this up!
- feat: Relocate Admin JS to `packages/admin` directory, and refactor for backwards-compatibility.
- fix: Correctly set the minimum supported WP version to 5.7. This should have been bumped in v0.0.7, but was missed.
- fix: Let the OAuth2 Provider library handle imploding the `scope`.
- fix: Use the correct label for the `scope` setting in the GitHub and LinkedIn provider settings.
- fix: Correctly pass the provider `$user_data` to the `graphql_login_after_successful_login` filter. Props @martinowren.
- dev: New actions: `graphql_login_after_authenticate`, `graphql_login_get_user_from_data`.
- dev: New filters: `graphql_login_authenticated_user_data`, `graphql_login_pre_get_user_from_data`,
`graphql_login_create_user_data`.
- dev: Deprecated the `graphql_login_mapped_user_data` filter in favor of `graphql_login_authenticated_user_data`.
- chore: Update Composer deps.
- chore: Cleanup PHPCS and PHPStan configurations.
- ci: Set MariaDB to v10.x in GitHub Actions.
- ci: Test against PHP 8.2 and WordPress 6.3.
- docs: Cleanup Action and Filter references.

## [0.1.2] - 2023-6-05

This minor release implements the new WPGraphQL Coding Standards ruleset for `PHP_CodeSniffer`. While many of the addressed sniffs are cosmetic, numerous smells regarding performance, type safety, sanitization, and 3rd-party interoperability have been fixed as well.

- chore: Implement `axepress/wp-graphql-cs` PHP_Codesniffer ruleset.
- chore: Update WPGraphQL Plugin Boilerplate to v0.0.9.
- chore: Update Composer dev-dependencies.

## [0.1.1] - 2023-5-26

This minor release adds support for setting the `Access-Control-Allow-Credentials` header via the Headless Login settings panel. We also updated the Server-side Auth example based on the feedback and issues discovered during the [WPE Builders session](https://youtu.be/RnJZ8VRjtBk).

### What's changed

- feat: Add support for setting `Access-Control-Allow-Credentials` header via the Headless Login settings panel. Props @ArkDouglas.
- dev: Make anonymous functions static where possible to reduce memory usage.
- chore: Update Composer and NPM dependencies.
- ci: Fix typo in `GRAPHQL_LOGIN_JWT_SECRET_KEY` when defining constants for test ehttps://youtu.be/RnJZ8VRjtBknvironment.
- docs: Update the Server-side Auth example, and add references to the AxePress Playground demo. 

## [0.1.0] - 2023-4-27

This minor release bumps the version of the plugin to v0.1.0 to reflect the fact that it is now in a stable state. This means future releases will be SemVer compliant.

We also squashed a few bugs

### What's changed

- fix: Restore missing props on the `FormTokenControl` component. Thanks @ArkDouglas for reporting!
- chore: Update NPM deps.
- docs: Fix WooGraphQL example in Server-Side example. Thanks @kidunot89 for the help!

## [0.0.9] - 2023-04-22

- fix: Use `TokenManager::refresh_user_secret()` when revoking secrets on the backend to prevent `UserError`s for invalid secrets.
- fix: Hide `Password` provider from the list of User Profile identities.
- fix: Only start a PHP session if one is not already started.
- fix: Use `parent::register()` in `ClientOptions` and `LoginOptions` interface classes.
- dev: Show conflict `admin_notice` if WPGraphQL CORS is enabled.
- chore: Update Composer dependencies.
- ci: Check compatibility with WordPress 6.2
- ci: Only test extensions against latest WP/PHP version.
- tests: Fix `HttpClient` mocks for headers and body.
- docs: Rewrite and restructure existing docs.
- docs: Update Next API Routes example.
- docs: Add example for using NextAuth.js

## [0.0.8] - 2023-04-05

This release fixes a bug where OAuth2 (Generic) provider settings were not being accessed correctly (#56).

To address this, the provider slug was renamed from `generic-oauth2` to `oauth2-generic`.

**Note:** As a result, the `LoginProviderEnum` name for this provider has changed from `GENERIC_OAUTH2` to `OAUTH2_GENERIC`, and `GenericClientOptions` and `GenericLoginOptions` have been renamed to `OAuth2ClientOptions` and `OAuth2LoginOptions`, respectively. The OAuth2 Generic provider settings are **not** preserved.

- dev!: Rename `OAuth2 (Generic)` provider slug to `oauth2-generic`.
- fix: Use `ProviderConfig::get_slug()` for Provider registry keys. H/t @ryntab and @stephane-segning.


## [0.0.7] - 2023-03-24

- fix: Only create one notice when the SiteToken mutation cannot be enabled.
- fix: Prevent PHP notice when the SiteToken header key is not set.
- chore: Update Composer and NPM dependencies.

## [0.0.6] - 2023-03-17

This release adds support for a special Site Token provider, which allows users to authenticate with a custom Header and a external resource identity. It also adds support for setting Access Control headers, and removes the `loginWithPassword` mutation in favor of a `Password` provider that can be used with `login` mutation.

- feat!: Remove `loginWithPassword` mutation in favor of Password provider.
- feat: Add Site Token provider.
- feat: Add support for setting Access Control headers.
- fix: check OAuth2 state against current `$_SESSION`.
- fix: remove `codecept_debug()` call in plugin `src`.
- dev!: Refactor settings page frontend components and app logic.
- dev!: Refactor `ProviderConfig` methods.
- dev!: Make `oauthResponse` input optional.
- dev! Remove `graphql_login_before_password_authenticate` in favor of the existing `graphql_login_before_authenticate` filter.
- dev!: Remove `graphql_login_after_successful_password_login` in favor of the existing `graphql_login_after_successful_login` filter.
- dev: Add the `graphql_login_access_control_settings` filter.
- dev: Refactor settings registration and screen for better extendability.
- chore: Update NPM dependencies.
- chore: Update Composer dependencies.
- docs: Update docs to reflect new settings and providers.
- ci: Don't set `WP_AUTO_UPDATE_CORE` and `AUTOMATIC_UPDATED_DISABLED` when spinning up a Docker instance.
- tests: Rename provider mutation test files.
- tests: Backfill tests for Settings registry.
- tests: Backfill tests for `OAuth2 Generic` provider.



## [0.0.5] - 2023-02-26

- dev: relocate Strauss dependencies to `vendor-prefixed`.
- dev: wrap activation and deactivation global functions in `function_exists()` checks.
- chore: update Strauss and Composer deps.
- chore: update NPM dependencies.

## [0.0.4] - 2023-02-09

This release adds support for setting a WP Authentication Cookie on successful login, as well as compatibility with WPGraphQL for WooCommerce. It also fixes a handful of bugs, and backfills/refactors CI tests.

### Breaking Changes
- fix!: Use the provider slug to generate `LoginProviderEnum` names. This is a breaking change, as the name for Generic - OAuth2 is now `GENERIC_OAUTH2`.

### Added
- feat: Add setting to disable the `loginWithPassword` mutation.
- feat: Add settings to set a WP authentication cookie on successful login.
- feat: Add support for WPGraphQL for WooCommerce.

### Fixed
- fix: Don't overwrite existing `Access-Control-Expose-Headers` when adding `X-WPGraphQL-Login-Refresh-Token`.
- fix: Check for truthy values when using `graphql_get_login_setting()`.
- fix: Return `401` for user ID of `0` when validating authentication tokens.
- fix: use `WPGraphQL::debug()` instead of constant when adding headers.

### Changed
- dev: Trigger `wp_login` action on successful login.

### Housekeeping
- chore: (PHPCS) Fix `minimum_supported_wp_version`.
- chore: Update Composer dependencies.
- chore: Update NPM dependencies.
- ci: Update workflow actions to latest versions.
- ci: Fix XDebug install in Docker for PHP 7.x.
- ci: Add `INCLUDE_EXTENSIONS` env variable for running 3rd-party plugin tests.
- tests: Refactor helper methods.
- tests: refactor functional `Cept` tests to `Cest` format.
- tests: Add test for `NONE` provider enum.
- tests: Add test for `Utils::is_current_user()` with empty user supplied.
- tests: Backfill case for `TypeRegistry::get_registered_types()`.
- tests: Add acceptance test for login with WC enabled.

## [0.0.3] - 2022-12-03

This release adds support for Instagram and LinkedIn OAuth 2.0 providers, and fixes various typos and styling issues.

### Breaking Changes
- Schema: `linkExistingUsers` field was moved from the `LoginOptions` interface, to the individual `{Provider}LoginOptions` objects that implement that setting.

### Added
- feat: Add Instagram provider support.
- feat: Add LinkedIn provider support.

### Changed
- dev!: Move `loginOptions.linkExistingUsers` to `{Provider}LoginOptions`.

### Fixed
- fix: Remove trailing `.` from title and action strings.

### Housekeeping
- dev: Update Strauss and Composer deps.
- docs: Fix various typos and styling issues. Thanks @jasonbahl !
- ci: Upgrade workflow actions to latest versions.

## [0.0.2] - 2022-11-27

### Added
- feat: Improve requirement checks for `WPGraphQL` (required: v1.12.0) and `WPGraphQL-JWT-Authentication`(conflicted) plugins.

### Changed
- dev: Allow core function overloading in `wp-graphql-headless-login.php`.

### Fixed
- fix: Correctly map Github first and last name to user data.

### Housekeeping
- chore: Update doc-block header in `src/Type/WPObject/LoginOptions.php`
- docs: Fix broken Readme.md links.
- tests: add tests for Github and Google provider mutations.

## [0.0.1] - 2022-11-26 - Initial (Public) Release
