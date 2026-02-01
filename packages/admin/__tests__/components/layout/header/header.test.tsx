import { render, screen } from '@testing-library/react';
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

function renderWithProviders( ui: React.ReactElement ) {
	const wrapper = ( { children }: { children: React.ReactNode } ) => (
		<SettingsProvider>
			<ScreenProvider>{ children }</ScreenProvider>
		</SettingsProvider>
	);

	return {
		...render( ui, { wrapper } ),
	};
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
		it( 'renders header element', () => {
			const { container } = renderWithProviders( <Header /> );

			const header = container.querySelector( 'header' );
			expect( header ).toBeInTheDocument();
		} );
	} );

	describe( 'Child components', () => {
		it( 'renders Logo component', () => {
			renderWithProviders( <Header /> );

			const logo = screen.getByTestId( 'logo' );
			expect( logo ).toBeInTheDocument();
		} );

		it( 'renders Logo with correct size', () => {
			renderWithProviders( <Header /> );

			const logo = screen.getByTestId( 'logo' );
			expect( logo ).toHaveAttribute( 'data-size', '90' );
		} );

		it( 'renders Menu component', () => {
			renderWithProviders( <Header /> );

			const menu = screen.getByTestId( 'menu' );
			expect( menu ).toBeInTheDocument();
		} );

		it( 'renders AdvancedSettingsToggle component', () => {
			renderWithProviders( <Header /> );

			const toggle = screen.getByTestId( 'advanced-settings-toggle' );
			expect( toggle ).toBeInTheDocument();
		} );
	} );

	describe( 'Layout structure', () => {
		it( 'renders title in menu section', () => {
			renderWithProviders( <Header /> );

			const title = screen.getByText( 'Headless Login Settings' );
			expect( title ).toBeInTheDocument();
		} );
	} );
} );
