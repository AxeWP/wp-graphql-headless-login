# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to a modified version of [Semantic Versioning](./README.md#updating-and-versioning).

## Unreleased

### Added
- feat: Add Instagram provider support.

### Changed
- dev: Make `loginOptions.linkExistingUsers` more modular.

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
