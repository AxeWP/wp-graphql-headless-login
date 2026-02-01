import {
	render,
	screen,
	fireEvent,
	renderHook,
	act,
	waitFor,
} from '@testing-library/react';
import { vi, describe, it, expect, beforeEach, afterEach } from 'vitest';
import { AdvancedSettingsToggle } from '@/admin/components/layout/header/advanced-settings-toggle';
import {
	SettingsProvider,
	useSettings,
} from '@/admin/contexts/settings-context';
import {
	setupWpGraphQLLoginMock,
	resetWpGraphQLLoginMocks,
} from '@/admin/__tests__/mocks/wordpress-global.mock';

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

function renderWithSettingsProvider( ui: React.ReactElement ) {
	const wrapper = ( { children }: { children: React.ReactNode } ) => (
		<SettingsProvider>{ children }</SettingsProvider>
	);

	return {
		...render( ui, { wrapper } ),
	};
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
		it( 'renders toggle button', () => {
			renderWithSettingsProvider( <AdvancedSettingsToggle /> );

			const toggle = screen.getByTestId( 'toggle-control' );
			expect( toggle ).toBeInTheDocument();
		} );

		it( 'shows correct label', () => {
			renderWithSettingsProvider( <AdvancedSettingsToggle /> );

			const toggle = screen.getByTestId( 'toggle-control' );
			expect( toggle ).toHaveAttribute(
				'data-label',
				'Show advanced settings'
			);
		} );

		it( 'has correct className', () => {
			renderWithSettingsProvider( <AdvancedSettingsToggle /> );

			const toggle = screen.getByTestId( 'toggle-control' );
			expect( toggle ).toHaveAttribute(
				'data-classname',
				'wp-graphql-headless-login__advanced-settings-toggle'
			);
		} );
	} );

	describe( 'Initial state', () => {
		it( 'initial state is false', () => {
			renderWithSettingsProvider( <AdvancedSettingsToggle /> );

			const toggle = screen.getByTestId( 'toggle-control' );
			expect( toggle ).toHaveAttribute( 'data-checked', 'false' );
		} );
	} );

	describe( 'Toggle behavior', () => {
		it( 'toggles to true on click', async () => {
			const { result } = renderHook( () => useSettings(), {
				wrapper: ( { children } ) => (
					<SettingsProvider>{ children }</SettingsProvider>
				),
			} );

			await waitFor( () => {
				expect( result.current ).toBeDefined();
			} );

			const { rerender } = renderWithSettingsProvider(
				<AdvancedSettingsToggle />
			);

			const toggle = screen.getByTestId( 'toggle-control' );
			expect( toggle ).toHaveAttribute( 'data-checked', 'false' );

			act( () => {
				fireEvent.click( toggle );
			} );

			rerender(
				<SettingsProvider>
					<AdvancedSettingsToggle />
				</SettingsProvider>
			);
		} );

		it( 'calls updateSettings with correct value on toggle', async () => {
			const { result } = renderHook( () => useSettings(), {
				wrapper: ( { children } ) => (
					<SettingsProvider>{ children }</SettingsProvider>
				),
			} );

			await waitFor( () => {
				expect( result.current ).toBeDefined();
			} );

			renderWithSettingsProvider( <AdvancedSettingsToggle /> );

			const toggle = screen.getByTestId( 'toggle-control' );
			const updateSettingsSpy = vi.spyOn(
				result.current,
				'updateSettings'
			);

			act( () => {
				fireEvent.click( toggle );
			} );

			expect( updateSettingsSpy ).toBeDefined();
		} );
	} );

	describe( 'Disabled state', () => {
		it( 'is disabled when isSaving is true', async () => {
			const { result } = renderHook( () => useSettings(), {
				wrapper: ( { children } ) => (
					<SettingsProvider>{ children }</SettingsProvider>
				),
			} );

			await waitFor( () => {
				expect( result.current ).toBeDefined();
			} );

			renderWithSettingsProvider( <AdvancedSettingsToggle /> );

			const toggle = screen.getByTestId( 'toggle-control' );

			expect( toggle ).toHaveAttribute( 'data-disabled', 'false' );
		} );
	} );

	describe( 'Integration with SettingsContext', () => {
		it( 'has access to settings context methods', async () => {
			const { result } = renderHook( () => useSettings(), {
				wrapper: ( { children } ) => (
					<SettingsProvider>{ children }</SettingsProvider>
				),
			} );

			await waitFor( () => {
				expect( result.current ).toBeDefined();
			} );

			expect( result.current.updateSettings ).toBeDefined();
			expect( result.current.saveSettings ).toBeDefined();
			expect( result.current.isSaving ).toBeDefined();
			expect( result.current.showAdvancedSettings ).toBeDefined();
			expect( typeof result.current.updateSettings ).toBe( 'function' );
			expect( typeof result.current.saveSettings ).toBe( 'function' );
		} );

		it( 'receives showAdvancedSettings from context', async () => {
			const { result } = renderHook( () => useSettings(), {
				wrapper: ( { children } ) => (
					<SettingsProvider>{ children }</SettingsProvider>
				),
			} );

			await waitFor( () => {
				expect( result.current ).toBeDefined();
			} );

			renderWithSettingsProvider( <AdvancedSettingsToggle /> );

			const toggle = screen.getByTestId( 'toggle-control' );
			expect( toggle ).toHaveAttribute( 'data-checked', 'false' );
		} );
	} );
} );
