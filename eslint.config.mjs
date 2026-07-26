/**
 * ESLint (flat) config.
 *
 * Extends the shared AxeWP base config and layers on plugin-specific settings.
 *
 * @see https://eslint.org/docs/latest/use/configure/configuration-files
 */
import base from '@axepress/plugin-infra/eslint';

export default [
	{
		ignores: [
			'**/*.min.js',
			".claude/**",
			'build/**',
			'coverage/**',
			'languages/**',
			'node_modules/**',
			'tools/**',
			'tests/_output/**',
			'vendor/**',
			'vendor-prefixed/**',
		],
	},

	...base,

	{
		rules: {
			'@wordpress/i18n-text-domain': [
				'error',
				{
					allowedTextDomain: 'wp-graphql-headless-login',
				},
			],
			// @todo disable upstream.
			'@wordpress/dependency-group': 'off',
			// This project bundles its own Vitest tooling.
			'import/no-extraneous-dependencies': [
				'error',
				{
					devDependencies: [
						'**/*.@(spec|test).@(j|t)s?(x)',
						'**/@(webpack|babel|vite|vitest).config.@(j|t)s',
						'**/vitest.setup.@(j|t)s',
						'**/scripts/**',
						'**/tests/**',
						'**/__tests__/**',
					],
				},
			],
		},
	},

	// This project uses the `_`-prefix convention for intentionally unused vars.
	{
		files: [ '**/*.ts', '**/*.tsx' ],
		rules: {
			'@typescript-eslint/no-unused-vars': [
				'error',
				{
					argsIgnorePattern: '^_',
					caughtErrorsIgnorePattern: '^_',
					varsIgnorePattern: '^_',
					ignoreRestSiblings: true,
				},
			],
		},
	},

	// Vitest test files: expose the test globals and relax type strictness.
	{
		files: [
			'**/__tests__/**/*.{ts,tsx}',
			'**/*.{test,spec}.{ts,tsx}',
			'vitest.setup.ts',
		],
		languageOptions: {
			globals: {
				afterAll: 'readonly',
				afterEach: 'readonly',
				beforeAll: 'readonly',
				beforeEach: 'readonly',
				describe: 'readonly',
				expect: 'readonly',
				it: 'readonly',
				suite: 'readonly',
				test: 'readonly',
				vi: 'readonly',
				vitest: 'readonly',
			},
		},
		rules: {
			'@typescript-eslint/no-explicit-any': 'off',
		},
	},
];
