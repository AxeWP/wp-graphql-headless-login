# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to a modified version of [Semantic Versioning](./README.md#updating-and-versioning).

## Unreleased
- feat!: Remove `loginWithPassword` mutation in favor of Password provider.
- feat: Add Site Token provider.
- feat: Add support for setting Access Control headers.
- fix: check OAuth2 state against current `$_SESSION`.
- dev!: Refactor settings page frontend components and app logic.
- dev!: Refactor `ProviderConfig` methods.
- dev!: Make `oauthResponse` input optional.
- dev: Refactor settings registration for better extendability.
- chore: update NPM dependencies.
- chore: update Composer dependencies.
- tests: Rename provider mutation test files.
- tests: add tests for Settings registry.
- tests: add tests for `OAuth2 Generic` provider.

## [0.0.5] - 2022-02-26

- dev: relocate Strauss dependencies to `vendor-prefixed`.
- dev: wrap activation and deactivation global functions in `function_exists()` checks.
- chore: update Strauss and Composer deps.
- chore: update NPM dependencies.

## [0.0.4] - 2022-02-09

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
