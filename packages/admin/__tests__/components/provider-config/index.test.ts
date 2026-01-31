import { describe, it, expect, vi } from 'vitest';

// Mock @wordpress/blocks to avoid JSON import errors
vi.mock( '@wordpress/blocks', () => ( {} ) );
vi.mock( '@wordpress/api-fetch' );
vi.mock( '@wordpress/data', () => ( {
	useDispatch: vi.fn( () => ( {} ) ),
	useSelect: vi.fn( () => ( {} ) ),
} ) );
vi.mock( '@wordpress/core-data', () => ( {
	store: {},
	useEntityProp: vi.fn(),
} ) );
vi.mock( '@wordpress/notices', () => ( {
	store: {},
} ) );

import {
	ClientSettings,
	ClientMenu,
	ClientPanel,
	ClientOptionList,
} from '@/admin/components/provider-config';

describe( 'provider-config index', () => {
	it( 'exports ClientSettings', () => {
		expect( ClientSettings ).toBeDefined();
		expect( typeof ClientSettings ).toBe( 'function' );
	} );

	it( 'exports ClientMenu', () => {
		expect( ClientMenu ).toBeDefined();
		expect( typeof ClientMenu ).toBe( 'function' );
	} );

	it( 'exports ClientPanel', () => {
		expect( ClientPanel ).toBeDefined();
		expect( typeof ClientPanel ).toBe( 'function' );
	} );

	it( 'exports ClientOptionList', () => {
		expect( ClientOptionList ).toBeDefined();
		expect( typeof ClientOptionList ).toBe( 'function' );
	} );
} );
