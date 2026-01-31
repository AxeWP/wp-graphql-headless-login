/// <reference types="vitest/globals" />

import type { Mock } from 'vitest';
import type { WpGraphQLLogin } from '../types.d';

declare global {
	describe: typeof vitest.describe;
	it: typeof vitest.it;
	test: typeof vitest.test;
	expect: typeof vitest.expect;
	beforeAll: typeof vitest.beforeAll;
	afterAll: typeof vitest.afterAll;
	beforeEach: typeof vitest.beforeEach;
	afterEach: typeof vitest.afterEach;
	vi: typeof vitest.vi;
	wpGraphQLLogin: WpGraphQLLogin;
}

export {};
