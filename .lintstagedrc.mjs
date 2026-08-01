/**
 * @type {import('lint-staged').Configuration}
 */
export default {
	'**/*.{js,jsx,ts,tsx}': [ 'npm run lint:js:fix' ],
	'**/*.{css,scss}': [ 'npm run lint:css:fix' ],
	/**
	 * Two separate hazards here, both load-bearing:
	 *
	 * 1. Quoting. lint-staged tokenizes the command with `string-argv` and
	 *    appends the filenames as argv — it does NOT run a shell. Interpolating
	 *    already-quoted filenames into an outer double-quoted `sh -c "…"` makes
	 *    the nested quotes collapse, and `phpcbf` ends up invoked with NO file
	 *    arguments. It then falls back to the `<file>` entries in the ruleset
	 *    and rewrites the whole tree — unstaged files, untracked files and
	 *    `node_modules` included, none of which lint-staged can revert. Hence
	 *    the single-quoted script plus `_` (as `$0`) and `"$@"`, which forwards
	 *    exactly the files lint-staged passed, spaces and all.
	 *
	 * 2. Exit codes. `phpcbf` returns non-zero when it *succeeds* at fixing,
	 *    and the meanings are version-dependent: 3.x uses 0/1/2/3, while 4.x
	 *    returns a bitmask (1 FIXABLE | 2 NON_FIXABLE | 4 FAILED_TO_FIX |
	 *    16 PROCESS_ERROR | 64 REQUIREMENTS_NOT_MET). Any code that decodes it
	 *    is wrong on one major version or the other, so discard it and let
	 *    `phpcs` be the gate: it runs after, on the fixed files, and a process
	 *    error surfaces there too since it shares the ruleset.
	 *
	 * @see https://github.com/PHPCSStandards/PHP_CodeSniffer/issues/184
	 */
	'**/*.php': [
		'sh -c \'./vendor/bin/phpcbf "$@" || true\' _',
		'./vendor/bin/phpcs',
	],
	'**/*.{json,md,yml,yaml}': [ 'npm run format' ],
};
