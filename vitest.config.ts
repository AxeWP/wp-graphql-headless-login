/// <reference types="vitest" />
import { defineConfig } from 'vitest/config';
import tsconfigPaths from 'vite-tsconfig-paths';

export default defineConfig( {
	plugins: [ tsconfigPaths() ],

	test: {
		globals: true,
		environment: 'jsdom',
		setupFiles: [ './vitest.setup.ts' ],
		include: [ '**/*.test.{ts,tsx}', '**/*.spec.{ts,tsx}' ],
		exclude: [ 'node_modules/', 'build/', '**/tests/**' ],
		reporters: [ 'default', 'junit' ],
		outputFile: './tests/_output/test-results/junit.xml',
		deps: {
			interopDefault: true,
		},

		coverage: {
			provider: 'v8',
			reportsDirectory: './tests/_output/js-coverage',
			reporter: [
				'text',
				'json',
				'json-summary',
				'lcov',
				[ 'html', { subdir: 'lcov-report' } ],
			],
			exclude: [
				'node_modules/',
				'build/',
				'**/*.config.{js,ts}',
				'**/*.d.ts',
				'**/types.d.ts',
				'vitest.setup.ts',
				'**/tests/**',
				'**/__tests__/**',
			],
			include: [ 'packages/admin/**/*.{ts,tsx}' ],
		},
	},
} );
