import { render, screen } from '@testing-library/react';
import { vi, beforeEach, afterEach } from 'vitest';
import { ClientSettings } from '@/admin/components/provider-config/ClientSettings';
import { useEntityProp } from '@wordpress/core-data';
import {
	setupWpGraphQLLoginMock,
	resetWpGraphQLLoginMocks,
} from '../../mocks/wordpress-global.mock';

vi.mock( '@wordpress/core-data', () => ( {
	useEntityProp: vi.fn(),
} ) );

vi.mock( '@/admin/components/provider-config/ClientMenu', () => ( {
	ClientMenu: vi.fn( () => <div data-testid="client-menu">ClientMenu</div> ),
} ) );

vi.mock( '@/admin/components/provider-config/ClientPanel', () => ( {
	ClientPanel: vi.fn( () => (
		<div data-testid="client-panel">ClientPanel</div>
	) ),
} ) );

vi.mock( '@wordpress/components', () => ( {
	...vi.importActual( '@wordpress/components' ),
	Flex: ( { children }: { children: React.ReactNode } ) => (
		<div data-testid="flex">{ children }</div>
	),
	FlexItem: ( {
		children,
		className,
	}: {
		children: React.ReactNode;
		className?: string;
	} ) => (
		<div data-testid="flex-item" className={ className }>
			{ children }
		</div>
	),
	FlexBlock: ( { children }: { children: React.ReactNode } ) => (
		<div data-testid="flex-block">{ children }</div>
	),
	Panel: ( {
		children,
		className,
	}: {
		children: React.ReactNode;
		className?: string;
	} ) => (
		<div data-testid="panel" className={ className }>
			{ children }
		</div>
	),
} ) );

describe( 'ClientSettings Component', () => {
	let mockSetClientConfig: ReturnType< typeof vi.fn >;

	beforeEach( () => {
		setupWpGraphQLLoginMock();
		mockSetClientConfig = vi.fn();
		vi.mocked( useEntityProp ).mockReturnValue( [
			{
				name: 'OAuth2',
				order: 1,
				slug: 'oauth2',
				isEnabled: true,
			},
			mockSetClientConfig,
			undefined,
		] );
	} );

	afterEach( () => {
		resetWpGraphQLLoginMocks();
		vi.clearAllMocks();
	} );

	describe( 'Component composition', () => {
		it( 'renders ClientMenu in sidebar', () => {
			render( <ClientSettings /> );

			const clientMenu = screen.getByTestId( 'client-menu' );
			expect( clientMenu ).toBeInTheDocument();
		} );

		it( 'renders ClientPanel in main content', () => {
			render( <ClientSettings /> );

			const clientPanel = screen.getByTestId( 'client-panel' );
			expect( clientPanel ).toBeInTheDocument();
		} );

		it( 'ProviderConfigProvider wraps children', () => {
			render( <ClientSettings /> );

			const clientMenu = screen.getByTestId( 'client-menu' );
			const clientPanel = screen.getByTestId( 'client-panel' );

			expect( clientMenu ).toBeInTheDocument();
			expect( clientPanel ).toBeInTheDocument();
		} );
	} );

	describe( 'Flex layout', () => {
		it( 'renders Flex container with correct alignment', () => {
			render( <ClientSettings /> );

			const clientMenu = screen.getByTestId( 'client-menu' );
			const clientPanel = screen.getByTestId( 'client-panel' );

			expect( clientMenu ).toBeInTheDocument();
			expect( clientPanel ).toBeInTheDocument();
		} );

		it( 'renders sidebar in FlexItem', () => {
			render( <ClientSettings /> );

			const clientMenu = screen.getByTestId( 'client-menu' );
			const flexItem = screen.getByTestId( 'flex-item' );

			expect( clientMenu ).toBeInTheDocument();
			expect( flexItem ).toBeInTheDocument();
			expect( flexItem ).toContainElement( clientMenu );
		} );

		it( 'renders main content in FlexBlock', () => {
			render( <ClientSettings /> );

			const clientPanel = screen.getByTestId( 'client-panel' );
			const panel = screen.getByTestId( 'panel' );

			expect( clientPanel ).toBeInTheDocument();
			expect( panel ).toBeInTheDocument();
			expect( panel ).toContainElement( clientPanel );
		} );
	} );

	describe( 'Integration', () => {
		it( 'composes ClientMenu and ClientPanel together', () => {
			const { container } = render( <ClientSettings /> );

			const clientMenu = screen.getByTestId( 'client-menu' );
			const clientPanel = screen.getByTestId( 'client-panel' );

			expect( clientMenu ).toBeInTheDocument();
			expect( clientPanel ).toBeInTheDocument();

			expect( container.firstChild ).toContainElement( clientMenu );
			expect( container.firstChild ).toContainElement( clientPanel );
		} );
	} );
} );
