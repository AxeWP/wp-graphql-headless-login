import { render, screen, fireEvent, act } from '@testing-library/react';
import { vi, describe, it, expect, beforeEach, afterEach } from 'vitest';
import { AdvancedSettingsToggle } from '@/admin/components/layout/header/advanced-settings-toggle';
import { SettingsProvider } from '@/admin/contexts/settings-context';
import {
	setupWpGraphQLLoginMock,
	resetWpGraphQLLoginMocks,
} from '@/admin/__tests__/mocks/wordpress-global.mock';
import apiFetch from '@wordpress/api-fetch';

vi.mock( '@wordpress/components', () => ( {
	ToggleControl: ( {
		checked,
		label,
		disabled,
		onChange,
		className,
	}: {
		checked: boolean;
		label: string;
		disabled?: boolean;
		onChange?: ( value: boolean ) => void;
		className?: string;
	} ) => (
		<button
			data-testid="toggle-control"
			data-label={ label }
			data-checked={ String( checked ) }
			data-disabled={ String( disabled || false ) }
			data-classname={ className || '' }
			onClick={ () => onChange && onChange( ! checked ) }
			disabled={ disabled }
		>
			{ label }
		</button>
	),
} ) );

vi.mock( '@wordpress/i18n', () => ( {
	__: ( text: string ) => text,
} ) );

// Echo saved values back so a POST round-trip reflects the new setting, the way the REST endpoint does.
vi.mock( '@wordpress/api-fetch', () => ( {
	default: vi.fn(
		async ( {
			method,
			data,
		}: {
			method?: string;
			data?: { slug: string; values: Record< string, unknown > };
		} ) =>
			method === 'POST' && data ? { [ data.slug ]: data.values } : {}
	),
} ) );

async function renderWithSettingsProvider( ui: React.ReactElement ) {
	const wrapper = ( { children }: { children: React.ReactNode } ) => (
		<SettingsProvider>{ children }</SettingsProvider>
	);

	const result = render( ui, { wrapper } );

	// Flush the SettingsProvider settings fetch so its state updates land inside act().
	await act( async () => {} );

	return result;
}

describe( 'AdvancedSettingsToggle Component', () => {
	beforeEach( () => {
		setupWpGraphQLLoginMock();
		vi.clearAllMocks();
	} );

	afterEach( () => {
		resetWpGraphQLLoginMocks();
		vi.clearAllMocks();
	} );

	describe( 'Basic rendering', () => {
		it( 'renders toggle button', async () => {
			await renderWithSettingsProvider( <AdvancedSettingsToggle /> );

			const toggle = screen.getByTestId( 'toggle-control' );
			expect( toggle ).toBeInTheDocument();
		} );

		it( 'shows correct label', async () => {
			await renderWithSettingsProvider( <AdvancedSettingsToggle /> );

			const toggle = screen.getByTestId( 'toggle-control' );
			expect( toggle ).toHaveAttribute(
				'data-label',
				'Show advanced settings'
			);
		} );

		it( 'has correct className', async () => {
			await renderWithSettingsProvider( <AdvancedSettingsToggle /> );

			const toggle = screen.getByTestId( 'toggle-control' );
			expect( toggle ).toHaveAttribute(
				'data-classname',
				'wp-graphql-headless-login__advanced-settings-toggle'
			);
		} );
	} );

	describe( 'Initial state', () => {
		it( 'initial state is false', async () => {
			await renderWithSettingsProvider( <AdvancedSettingsToggle /> );

			const toggle = screen.getByTestId( 'toggle-control' );
			expect( toggle ).toHaveAttribute( 'data-checked', 'false' );
		} );

		it( 'reflects the saved setting', async () => {
			vi.mocked( apiFetch ).mockResolvedValueOnce( {
				wpgraphql_login_settings: { show_advanced_settings: true },
			} );

			await renderWithSettingsProvider( <AdvancedSettingsToggle /> );

			expect( screen.getByTestId( 'toggle-control' ) ).toHaveAttribute(
				'data-checked',
				'true'
			);
		} );
	} );

	describe( 'Toggle behavior', () => {
		it( 'toggles to true on click', async () => {
			await renderWithSettingsProvider( <AdvancedSettingsToggle /> );

			const toggle = screen.getByTestId( 'toggle-control' );
			expect( toggle ).toHaveAttribute( 'data-checked', 'false' );

			await act( async () => {
				fireEvent.click( toggle );
			} );

			expect( toggle ).toHaveAttribute( 'data-checked', 'true' );
		} );

		it( 'saves the toggled value', async () => {
			await renderWithSettingsProvider( <AdvancedSettingsToggle /> );

			await act( async () => {
				fireEvent.click( screen.getByTestId( 'toggle-control' ) );
			} );

			expect( apiFetch ).toHaveBeenCalledWith(
				expect.objectContaining( {
					method: 'POST',
					data: {
						slug: 'wpgraphql_login_settings',
						values: { show_advanced_settings: true },
					},
				} )
			);
		} );
	} );

	describe( 'Disabled state', () => {
		it( 'is disabled while saving', async () => {
			vi.mocked( apiFetch )
				// Initial GET on mount.
				.mockResolvedValueOnce( {} )
				// The save never settles, so the toggle stays in the saving state.
				.mockReturnValueOnce( new Promise( () => {} ) );

			await renderWithSettingsProvider( <AdvancedSettingsToggle /> );

			const toggle = screen.getByTestId( 'toggle-control' );
			expect( toggle ).toHaveAttribute( 'data-disabled', 'false' );

			await act( async () => {
				fireEvent.click( toggle );
			} );

			expect( toggle ).toHaveAttribute( 'data-disabled', 'true' );
		} );
	} );
} );
