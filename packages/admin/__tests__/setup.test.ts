import { describe, it, expect, vi } from 'vitest';

export const addFilter = vi.fn();
export const applyFilters = vi.fn();
export const addAction = vi.fn();
export const doAction = vi.fn();

describe( 'Test setup verification', () => {
	it( 'Vitest is configured correctly', () => {
		expect( 1 + 1 ).toBe( 2 );
	} );

	it( 'Global functions are available', () => {
		expect( describe ).toBeDefined();
		expect( it ).toBeDefined();
		expect( expect ).toBeDefined();
		expect( vi ).toBeDefined();
	} );
} );
