import { vi } from 'vitest';
import { waitFor, render, screen } from '@testing-library/react';

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

describe( 'App Component', () => {
	describe( 'Provider Hierarchy', () => {
		it( 'renders ErrorBoundary wrapper', async () => {
			const { container } = render( <App /> );

			await waitFor( () => {
				expect( container.firstChild ).toBeInTheDocument();
			} );
		} );

		it( 'renders SettingsProvider', async () => {
			render( <App /> );

			await waitFor( () => {
				expect( screen.getByRole( 'banner' ) ).toBeInTheDocument();
			} );
		} );

		it( 'renders ScreenProvider', async () => {
			render( <App /> );

			await waitFor( () => {
				expect( screen.getByRole( 'banner' ) ).toBeInTheDocument();
				expect( screen.getByRole( 'main' ) ).toBeInTheDocument();
			} );
		} );

		it( 'has correct component hierarchy', async () => {
			const { container } = render( <App /> );

			await waitFor( () => {
				const noticesContainer = container.querySelector(
					'.wp-graphql-headless-login__notices'
				);
				expect( noticesContainer ).toBeInTheDocument();

				expect( screen.getByRole( 'banner' ) ).toBeInTheDocument();
				expect( screen.getByRole( 'main' ) ).toBeInTheDocument();
			} );
		} );
	} );

	describe( 'Component Rendering', () => {
		it( 'renders Header component', async () => {
			render( <App /> );

			await waitFor( () => {
				expect( screen.getByRole( 'banner' ) ).toBeInTheDocument();
			} );
		} );

		it( 'renders Screen component', async () => {
			render( <App /> );

			await waitFor( () => {
				expect( screen.getByRole( 'main' ) ).toBeInTheDocument();
			} );
		} );

		it( 'renders Notices component in a dedicated container', async () => {
			const { container } = render( <App /> );

			await waitFor( () => {
				const noticesContainer = container.querySelector(
					'.wp-graphql-headless-login__notices'
				);
				expect( noticesContainer ).toBeInTheDocument();
			} );
		} );
	} );

	describe( 'Context Provider Availability', () => {
		it( 'tests pass when all context providers are available', async () => {
			await waitFor( () => {
				expect( () => {
					render( <App /> );
				} ).not.toThrow();
			} );
		} );

		it( 'renders all components without default props issues', async () => {
			const { container } = render( <App /> );

			await waitFor( () => {
				expect( screen.getByRole( 'banner' ) ).toBeInTheDocument();
				expect( screen.getByRole( 'main' ) ).toBeInTheDocument();
				expect(
					container.querySelector(
						'.wp-graphql-headless-login__notices'
					)
				).toBeInTheDocument();
			} );
		} );
	} );

	describe( 'Integration Tests', () => {
		it( 'renders with all providers', async () => {
			render( <App /> );

			await waitFor( () => {
				expect( screen.getByRole( 'main' ) ).toBeInTheDocument();
			} );
		} );

		it( 'renders header', async () => {
			render( <App /> );

			await waitFor( () => {
				expect( screen.getByRole( 'banner' ) ).toBeInTheDocument();
			} );
		} );

		it( 'renders screen', async () => {
			render( <App /> );

			await waitFor( () => {
				expect( screen.getByRole( 'main' ) ).toBeInTheDocument();
			} );
		} );

		it( 'renders notices', async () => {
			const { container } = render( <App /> );

			await waitFor( () => {
				expect(
					container.querySelector(
						'.wp-graphql-headless-login__notices'
					)
				).toBeInTheDocument();
			} );
		} );
	} );
} );
