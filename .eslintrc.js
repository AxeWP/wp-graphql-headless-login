module.exports = {
	extends: [ 'plugin:@wordpress/eslint-plugin/recommended' ],
	plugins: [ 'import' ],
	parserOptions: {
		sourceType: 'module',
		ecmaFeatures: {
			jsx: true,
		},
		project: './tsconfig.json',
	},
	env: {
		browser: true,
		es6: true,
		node: true,
	},
	settings: {
		jsdoc: { mode: 'typescript' },
		settings: {
			'import/resolver': {
				typescript: {
					project: './tsconfig.json',
				},
			},
		},
	},
	rules: {
		'react-hooks/exhaustive-deps': 'error',
		'react/jsx-fragments': [ 'error', 'syntax' ],
		'@wordpress/no-global-active-element': 'warn',
		'@wordpress/i18n-text-domain': [
			'error',
			{
				allowedTextDomain: [ 'wp-graphql-headless-login' ],
			},
		],
		camelcase: [
			'error',
			{
				properties: 'never',
				ignoreGlobals: true,
			},
		],
	},
	overrides: [
		{
			files: [ '**/*.ts?(x)' ],
			parser: '@typescript-eslint/parser',
			extends: [ 'plugin:@typescript-eslint/recommended' ],
			rules: {
				'prefer-rest-params': 'off',
				'@typescript-eslint/no-explicit-any': 'error',
				'no-use-before-define': 'off',
				'@typescript-eslint/no-use-before-define': [ 'error' ],
				'jsdoc/require-param': 'off',
				'no-shadow': 'off',
				'@typescript-eslint/no-shadow': [ 'error' ],
				'@typescript-eslint/no-unused-vars': [
					'error',
					{ ignoreRestSiblings: true },
				],
				camelcase: 'off',
				'@typescript-eslint/naming-convention': [
					'error',
					{
						selector: [ 'method', 'variableLike' ],
						format: [ 'camelCase', 'PascalCase', 'UPPER_CASE' ],
						leadingUnderscore: 'allowSingleOrDouble',
						filter: {
							regex: 'webpack_public_path__',
							match: false,
						},
					},
					{
						selector: 'typeProperty',
						format: [ 'camelCase', 'snake_case' ],
						filter: {
							regex: 'API_FETCH_WITH_HEADERS|Block',
							match: false,
						},
					},
				],
			},
		},
	],
};
