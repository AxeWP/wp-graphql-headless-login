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
		// React best practices
		'react/jsx-boolean-value': 'error',
		'react/jsx-curly-brace-presence': [
			'error',
			{ props: 'never', children: 'never' },
		],
		'react-hooks/exhaustive-deps': 'error',
		'react/jsx-fragments': [ 'error', 'syntax' ],

		// WordPress-specific
		'@wordpress/no-global-active-element': 'warn',
		'@wordpress/data-no-store-string-literals': 'error',
		'@wordpress/wp-global-usage': 'error',
		'@wordpress/react-no-unsafe-timeout': 'error',
		'@wordpress/i18n-hyphenated-range': 'error',
		'@wordpress/i18n-no-flanking-whitespace': 'error',
		'@wordpress/i18n-text-domain': [
			'error',
			{
				allowedTextDomain: [ 'wp-graphql-headless-login' ],
			},
		],

		// Import plugin
		'import/default': 'error',
		'import/named': 'error',
		'import/no-extraneous-dependencies': [
			'error',
			{
				devDependencies: [
					'**/*.@(spec|test).@(j|t)s?(x)',
					'**/vitest.config.@(j|t)s',
					'**/vitest.setup.@(j|t)s',
					'**/@(webpack|jest|vite|vitest).config.@(j|t)s',
					'**/scripts/**',
				],
			},
		],
		'no-restricted-imports': [
			'error',
			{
				paths: [
					{
						name: 'lodash',
						message: 'Please use native functionality instead.',
					},
					{
						name: 'classnames',
						message:
							"Please use `clsx` instead. It's a lighter and faster drop-in replacement for `classnames`.",
					},
					{
						name: 'redux',
						importNames: [ 'combineReducers' ],
						message:
							'Please use `combineReducers` from `@wordpress/data` instead.',
					},
				],
			},
		],
		'no-restricted-syntax': [
			'error',
			{
				selector:
					'ImportDeclaration[source.value=/^@wordpress\\u002F.+\\u002F/]',
				message:
					'Path access on WordPress dependencies is not allowed.',
			},
			{
				selector: 'JSXAttribute[name.name="id"][value.type="Literal"]',
				message:
					'Do not use string literals for IDs; use withInstanceId instead.',
			},
			{
				selector:
					'CallExpression[callee.object.name="Math"][callee.property.name="random"]',
				message:
					"Do not use Math.random() to generate unique IDs; use withInstanceId instead. (If you're not generating unique IDs: ignore this message.)",
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
				'dot-notation': 'off',
				'@typescript-eslint/dot-notation': [ 'error' ],
			},
		},
		{
			files: [ '**/*.test.{ts,tsx}', '**/*.spec.{ts,tsx}' ],
			rules: {
				'@typescript-eslint/no-explicit-any': 'warn',
				'@typescript-eslint/no-unused-vars': 'off',
			},
		},
	],
};
