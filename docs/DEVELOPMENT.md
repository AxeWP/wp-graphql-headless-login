# Development Guidelines - Headless Login for WPGraphQL

This document covers local setup, code standards, testing, and releasing for `axepress/wp-graphql-headless-login`.

## Table of Contents

- [Directory Structure](#directory-structure)
- [Local setup](#local-setup)
- [Code Contributions (Pull Requests)](#code-contributions-pull-requests)
- [Running Tests](#running-tests)
- [Releasing](#releasing)

## Directory Structure

| Path               | Description                                                                                                |
| ------------------ | ---------------------------------------------------------------------------------------------------------- |
| `src/`             | The plugin PHP source, autoloaded under the `WPGraphQL\Login\` PSR-4 namespace.                            |
| `packages/admin/`  | The React admin settings app (TypeScript), bundled with `wp-scripts`/webpack.                              |
| `tests/phpunit/`   | PHPUnit integration tests, autoloaded under the `Tests\WPGraphQL\Login\` namespace.                        |
| `tools/phpstan/`   | PHPStan bootstrap constants and stubs for symbols that live outside the plugin.                            |
| `vendor-prefixed/` | Composer dependencies prefixed with [Strauss](https://github.com/BrianHenryIE/strauss) to avoid conflicts. |
| `docs/`            | Project documentation.                                                                                     |

## Local setup

### Prerequisites

- [Node.js](https://nodejs.org/): v24.12.0+ ([NVM](https://nvm.sh/) recommended)
- npm: v11.14.1+
- [Docker](https://www.docker.com/)
- Optional: [Composer](https://getcomposer.org/) (if you prefer to run the Composer tools locally instead of using wp-env's built-in Composer)

You can use Docker and the `wp-env` tool to set up a local development environment, instead of manually installing the specific testing versions of WordPress, PHP, and Composer. For more information, see the [wp-env documentation](https://developer.wordpress.org/block-editor/packages/packages-env/).

### Installation

1. Clone the repository:

   ```bash
   git clone https://github.com/AxeWP/wp-graphql-headless-login.git
   ```

2. Change into the project folder and install the NPM dependencies.

   ```bash
   # If you're using NVM, make sure to use the correct Node.js version:
   nvm install && nvm use

   # Then install the NPM dependencies:
   npm install
   ```

3. Start the local development environment:

   ```bash
   npm run wp-env start
   ```

   This will start a local WordPress environment with the plugin installed, and the following default configuration:

   - Site URL: <http://localhost:8888>
   - WP Admin URL: <http://localhost:8888/wp-admin/>
     - WP Admin Username: `admin`
     - WP Admin Password: `password`

4. Install the PHP dependencies using Composer, using either your local Composer installation or wp-env's built-in Composer:

   ```bash
   # With wp-env:
   npm run wp-env:cli -- composer install

   # Or with local Composer:
   composer install
   ```

5. Build the admin app:

   ```bash
   npm run build:prod

   # Or, for development with file watching:
   npm run start:js
   ```

### Useful Commands

#### Accessing the Local Environment

- `npm run wp-env start`: Start the local development environment.
- `npm run wp-env stop`: Stop the local development environment.
- `npm run wp-env:cli -- {YOUR_CMD_HERE}`: Run WP-CLI/Composer commands in the local environment.
- `npm run wp-env:test start`: Start the testing environment (used by `lint:php`, `lint:php:fix`, `lint:php:stan`, `test:php`, and `generate:schema`).
- `npm run wp-env clean all`: Resets the wp-env database.

#### Building

- `npm run build:prod`: Production build of the admin app.
- `npm run build:dev`: Development build.
- `npm run start:js`: Development build with file watching.
- `npm run plugin-zip`: Build the distributable plugin zip.

#### Linting and Formatting

- `npm run format`: Formats the codebase using Prettier.
- `npm run lint:js`: Runs ESLint on the JS/TS code.
- `npm run lint:js:fix`: Autofixes ESLint issues.
- `npm run lint:js:types`: Runs TypeScript's `tsc` to check for type errors.
- `npm run lint:css`: Runs Stylelint on the SCSS.
- `npm run lint:php`: Runs PHPCS linting on the PHP code.
- `npm run lint:php:fix`: Autofixes PHPCS linting issues.
- `npm run lint:php:stan`: Runs PHPStan static analysis on the PHP code.

## Code Contributions (Pull Requests)

### Workflow

This repository uses `main` as its default branch. Always create a new branch from `main` when working on a feature or bug fix.

Branches should be prefixed with the type of change (e.g. `feat`, `chore`, `tests`, `fix`, etc.) followed by a short description of the change. For example, a branch for a new feature called "Add new feature" could be named `feat/add-new-feature`.

Pull requests are **squash-merged** into `main`. Use a [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0/) title (for example, `feat: add settings page`) so the squash commit on `main` can drive automated releases.

### Code Quality / Code Standards

#### PHP_CodeSniffer

This project uses [PHP_CodeSniffer](https://github.com/PHPCSStandards/PHP_CodeSniffer/) with the [WPGraphQL Coding Standards](https://github.com/AxeWP/WPGraphQL-Coding-Standards) ruleset. Our specific ruleset is defined in [`.phpcs.xml.dist`](../.phpcs.xml.dist).

```bash
npm run lint:php      # check
npm run lint:php:fix  # autofix what can be autofixed
```

#### PHPStan

This project uses [PHPStan](https://phpstan.org/) for static analysis. Our configuration is defined in [`phpstan.neon.dist`](../phpstan.neon.dist).

```bash
npm run lint:php:stan
```

#### ESLint / Stylelint / Prettier

The JS/TS code is linted with [ESLint](https://eslint.org/) (config: [`eslint.config.mjs`](../eslint.config.mjs), extending `@axepress/plugin-infra/eslint`), the SCSS with [Stylelint](https://stylelint.io/), and everything is formatted with [wp-prettier](https://www.npmjs.com/package/wp-prettier) (config: [`.prettierrc.js`](../.prettierrc.js)).

```bash
npm run lint:js
npm run lint:css
npm run format
```

#### TypeScript

The admin app is type-checked with TypeScript 6's native `tsc` (config: [`tsconfig.json`](../tsconfig.json), extending `@axepress/plugin-infra/tsconfig`).

```bash
npm run lint:js:types
```

### Pre-commit Hooks

This project uses [Lefthook](https://lefthook.dev/) to manage Git hooks. The configuration is defined in [`.lefthook.yml`](../.lefthook.yml).

By default, lefthook calls [lint-staged](https://github.com/okonet/lint-staged) to run linters on staged files before each commit. The lint-staged configuration is defined in [`.lintstagedrc.mjs`](../.lintstagedrc.mjs).

## Running Tests

### PHPUnit

PHPUnit integration tests run against the wp-env testing environment:

```bash
npm run wp-env:test start
npm run test:php
```

To generate a code coverage report, make sure to start the testing environment with coverage mode enabled:

```bash
npm run wp-env:test start -- --xdebug=coverage

npm run test:php
```

> [!NOTE]
> The flag is `--xdebug=coverage`. `--xdebug-mode=coverage` is silently ignored by wp-env, so Xdebug never loads and the run fails with "No code coverage driver available".

You should see the HTML coverage report in the `tests/_output/html` directory and the clover XML report in `tests/_output/php-coverage.xml`.

### Vitest

The admin app's JS tests run with [Vitest](https://vitest.dev/):

```bash
npm run test:js            # single run
npm run test:js:watch      # watch mode
npm run test:js:coverage   # with coverage
```

### GraphQL Schema

To regenerate and lint the static GraphQL schema:

```bash
npm run wp-env:test start
npm run generate:schema
npm run lint:schema
```

### GitHub Workflows

GitHub workflows run the lints and tests on pull requests and on `main` and `main`. The entrypoint is [`.github/workflows/ci.yml`](../.github/workflows/ci.yml), which delegates to the reusable workflows in [AxeWP/plugin-infra](https://github.com/AxeWP/plugin-infra/tree/main/.github/workflows).

## Releasing

Releases are automated with [Release Please](https://github.com/googleapis/release-please-action) and Conventional Commits.
