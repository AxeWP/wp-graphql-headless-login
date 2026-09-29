import { render, screen, act } from '@testing-library/react';
import { vi, describe, it, expect, beforeEach, afterEach } from 'vitest';
import { Header } from '@/admin/components/layout/header';
import { SettingsProvider } from '@/admin/contexts/settings-context';
import { ScreenProvider } from '@/admin/components/screen/context';
import {
	setupWpGraphQLLoginMock,
	resetWpGraphQLLoginMocks,
} from '@/admin/__tests__/mocks/wordpress-global.mock';

vi.mock( '@/admin/components/logo', () => ( {
	Logo: ( { size }: { size: number } ) => (
		<div data-testid="logo" data-size={ size }>
			Logo
		</div>
	),
} ) );

vi.mock( '@/admin/components/layout/header/menu', () => ( {
	Menu: () => <div data-testid="menu">Menu</div>,
} ) );

vi.mock( '@/admin/components/layout/header/advanced-settings-toggle', () => ( {
	AdvancedSettingsToggle: () => (
		<div data-testid="advanced-settings-toggle">
			Advanced Settings Toggle
		</div>
	),
} ) );

vi.mock( '@wordpress/i18n', () => ( {
	__: ( text: string ) => text,
} ) );

vi.mock( '@wordpress/api-fetch', () => ( {
	default: vi.fn().mockResolvedValue( {} ),
} ) );

async function renderWithProviders( ui: React.ReactElement ) {
	const wrapper = ( { children }: { children: React.ReactNode } ) => (
		<SettingsProvider>
			<ScreenProvider>{ children }</ScreenProvider>
		</SettingsProvider>
	);

	const result = render( ui, { wrapper } );

	// Flush the SettingsProvider settings fetch so its state updates land inside act().
	await act( async () => {} );

	return result;
}

describe( 'Header Component', () => {
	beforeEach( () => {
		setupWpGraphQLLoginMock();
		vi.clearAllMocks();
	} );

	afterEach( () => {
		resetWpGraphQLLoginMocks();
		vi.clearAllMocks();
	} );

	describe( 'Component structure', () => {
		it( 'renders header element', async () => {
			const { container } = await renderWithProviders( <Header /> );

			const header = container.querySelector( 'header' );
			expect( header ).toBeInTheDocument();
		} );
	} );

	describe( 'Child components', () => {
		it( 'renders Logo component', async () => {
			await renderWithProviders( <Header /> );

			const logo = screen.getByTestId( 'logo' );
			expect( logo ).toBeInTheDocument();
		} );

		it( 'renders Logo with correct size', async () => {
			await renderWithProviders( <Header /> );

			const logo = screen.getByTestId( 'logo' );
			expect( logo ).toHaveAttribute( 'data-size', '90' );
		} );

		it( 'renders Menu component', async () => {
			await renderWithProviders( <Header /> );

			const menu = screen.getByTestId( 'menu' );
			expect( menu ).toBeInTheDocument();
		} );

		it( 'renders AdvancedSettingsToggle component', async () => {
			await renderWithProviders( <Header /> );

			const toggle = screen.getByTestId( 'advanced-settings-toggle' );
			expect( toggle ).toBeInTheDocument();
		} );
	} );

	describe( 'Layout structure', () => {
		it( 'renders title in menu section', async () => {
			await renderWithProviders( <Header /> );

			const title = screen.getByText( 'Headless Login Settings' );
			expect( title ).toBeInTheDocument();
		} );
	} );
} );
