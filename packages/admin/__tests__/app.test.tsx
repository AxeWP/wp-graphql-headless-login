import { vi } from 'vitest';

// Mock @wordpress/blocks to avoid JSON import errors
vi.mock( '@wordpress/blocks', () => ( {} ) );
vi.mock( '@wordpress/api-fetch', () => ( {
	default: vi.fn( () => Promise.resolve( {} ) ),
} ) );
vi.mock( '@wordpress/data', () => ( {
	useDispatch: vi.fn( () => ( {
		createNotice: vi.fn(),
		createErrorNotice: vi.fn(),
	} ) ),
	useSelect: vi.fn( () => ( {
		lastError: null,
		isSaving: false,
		hasEdits: false,
	} ) ),
} ) );
vi.mock( '@wordpress/core-data', () => ( {
	store: {},
	useEntityProp: vi.fn( () => [ {}, vi.fn() ] ),
} ) );
vi.mock( '@wordpress/notices', () => ( {
	store: {},
} ) );

import App from '../app';
import { render, screen } from '@testing-library/react';

describe( 'App Component', () => {
	describe( 'Provider Hierarchy', () => {
		it( 'renders ErrorBoundary wrapper', () => {
			const { container } = render( <App /> );

			expect( container.firstChild ).toBeInTheDocument();
		} );

		it( 'renders SettingsProvider', () => {
			render( <App /> );

			expect( screen.getByRole( 'banner' ) ).toBeInTheDocument();
		} );

		it( 'renders ScreenProvider', () => {
			render( <App /> );

			expect( screen.getByRole( 'banner' ) ).toBeInTheDocument();
			expect( screen.getByRole( 'main' ) ).toBeInTheDocument();
		} );

		it( 'has correct component hierarchy', () => {
			const { container } = render( <App /> );

			const noticesContainer = container.querySelector(
				'.wp-graphql-headless-login__notices'
			);
			expect( noticesContainer ).toBeInTheDocument();

			expect( screen.getByRole( 'banner' ) ).toBeInTheDocument();
			expect( screen.getByRole( 'main' ) ).toBeInTheDocument();
		} );
	} );

	describe( 'Component Rendering', () => {
		it( 'renders Header component', () => {
			render( <App /> );

			expect( screen.getByRole( 'banner' ) ).toBeInTheDocument();
		} );

		it( 'renders Screen component', () => {
			render( <App /> );

			expect( screen.getByRole( 'main' ) ).toBeInTheDocument();
		} );

		it( 'renders Notices component in a dedicated container', () => {
			const { container } = render( <App /> );

			const noticesContainer = container.querySelector(
				'.wp-graphql-headless-login__notices'
			);
			expect( noticesContainer ).toBeInTheDocument();
		} );
	} );

	describe( 'Context Provider Availability', () => {
		it( 'tests pass when all context providers are available', () => {
			expect( () => {
				render( <App /> );
			} ).not.toThrow();
		} );

		it( 'renders all components without default props issues', () => {
			const { container } = render( <App /> );

			expect( screen.getByRole( 'banner' ) ).toBeInTheDocument();
			expect( screen.getByRole( 'main' ) ).toBeInTheDocument();
			expect(
				container.querySelector( '.wp-graphql-headless-login__notices' )
			).toBeInTheDocument();
		} );
	} );

	describe( 'Integration Tests', () => {
		it( 'renders with all providers', () => {
			render( <App /> );

			expect( screen.getByRole( 'main' ) ).toBeInTheDocument();
		} );

		it( 'renders header', () => {
			render( <App /> );

			expect( screen.getByRole( 'banner' ) ).toBeInTheDocument();
		} );

		it( 'renders screen', () => {
			render( <App /> );

			expect( screen.getByRole( 'main' ) ).toBeInTheDocument();
		} );

		it( 'renders notices', () => {
			const { container } = render( <App /> );

			expect(
				container.querySelector( '.wp-graphql-headless-login__notices' )
			).toBeInTheDocument();
		} );
	} );
} );
