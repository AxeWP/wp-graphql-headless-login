import '@testing-library/jest-dom';
import { cleanup } from '@testing-library/react';
import { afterEach, vi } from 'vitest';
import { setupWpGraphQLLoginMock } from './packages/admin/__tests__/mocks/wordpress-global.mock';

setupWpGraphQLLoginMock();

vi.mock( '@wordpress/blocks', () => ( {} ) );

const MockLogoSVG = () => '<svg data-testid="logo-svg" />';

vi.mock( '@/admin/assets/logo.svg', () => ( {
	ReactComponent: MockLogoSVG,
} ) );

afterEach( () => {
	cleanup();
} );

Object.defineProperty( window, 'matchMedia', {
	writable: true,
	value: vi.fn().mockImplementation( ( query: string ) => ( {
		matches: false,
		media: query,
		onchange: null,
		addListener: vi.fn(),
		removeListener: vi.fn(),
		addEventListener: vi.fn(),
		removeEventListener: vi.fn(),
		dispatchEvent: vi.fn(),
	} ) ),
} );

class MockResizeObserver {
	observe = vi.fn();
	unobserve = vi.fn();
	disconnect = vi.fn();
}

vi.stubGlobal( 'ResizeObserver', MockResizeObserver );
