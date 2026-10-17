import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { Menu } from '@/admin/components/layout/header/menu';
import {
	SettingsProvider,
	useSettings,
} from '@/admin/contexts/settings-context';
import {
	ScreenProvider,
	useCurrentScreen,
} from '@/admin/components/screen/context';
import {
	setupWpGraphQLLoginMock,
	resetWpGraphQLLoginMocks,
} from '@/admin/__tests__/mocks/wordpress-global.mock';
import apiFetch from '@wordpress/api-fetch';

interface WpGraphQLLoginGlobal {
	settings: Record< string, unknown >;
	providers: Record< string, unknown >;
}

vi.mock( '@wordpress/api-fetch' );

// Partial mock: `@wordpress/components` imports many i18n functions at import time, so pass through everything except what the tests control.
vi.mock( '@wordpress/i18n', async ( importOriginal ) => ( {
	...( await importOriginal< typeof import( '@wordpress/i18n' ) >() ),
	__: ( text: string ) => text,
} ) );

vi.mock( '@/admin/components/layout/header/menu/styles.module.scss', () => ( {
	default: {
		active: 'active',
		menu: 'menu',
		dirtyIndicator: 'dirtyIndicator',
		linkIcon: 'linkIcon',
	},
} ) );

// Edits a setting, making the settings dirty.
const MakeDirty = () => {
	const { updateSettings } = useSettings();

	return (
		<button
			type="button"
			onClick={ () =>
				updateSettings( {
					slug: 'wpgraphql_login_settings',
					values: { test: 'changed' },
				} )
			}
		>
			make-dirty
		</button>
	);
};

const CurrentScreen = () => (
	<output data-testid="current-screen">
		{ useCurrentScreen().currentScreen }
	</output>
);

const MenuWithProbes = () => (
	<>
		<Menu />
		<MakeDirty />
		<CurrentScreen />
	</>
);

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

describe( 'Menu Component', () => {
	beforeEach( () => {
		setupWpGraphQLLoginMock();
		vi.clearAllMocks();
	} );

	afterEach( () => {
		resetWpGraphQLLoginMocks();
		vi.clearAllMocks();
	} );

	describe( 'getMenuObject utility function', () => {
		it( 'builds menu object from settings', async () => {
			vi.mocked( apiFetch ).mockResolvedValue( {} );

			(
				global as unknown as { wpGraphQLLogin: WpGraphQLLoginGlobal }
			 ).wpGraphQLLogin = {
				settings: {
					wpgraphql_login_test_setting: {
						label: 'Test Setting',
					},
					wpgraphql_login_another_setting: {
						label: 'Another Setting',
					},
				},
				providers: {},
			};

			renderWithProviders( <Menu /> );

			await waitFor( () => {
				expect(
					screen.getByText( 'Test Setting' )
				).toBeInTheDocument();
				expect(
					screen.getByText( 'Another Setting' )
				).toBeInTheDocument();
			} );
		} );

		it( 'uses the providers label for settings without a label', async () => {
			vi.mocked( apiFetch ).mockResolvedValue( {} );

			(
				global as unknown as { wpGraphQLLogin: WpGraphQLLoginGlobal }
			 ).wpGraphQLLogin = {
				settings: {
					wpgraphql_login_unlabeled: {},
				},
				providers: {},
			};

			renderWithProviders( <Menu /> );

			await waitFor( () => {
				expect( screen.getAllByText( 'Providers' ) ).toHaveLength( 2 );
			} );
		} );

		it( 'converts setting keys to screen names', async () => {
			vi.mocked( apiFetch ).mockResolvedValue( {} );

			(
				global as unknown as { wpGraphQLLogin: WpGraphQLLoginGlobal }
			 ).wpGraphQLLogin = {
				settings: {
					wpgraphql_login_access_control: {
						label: 'Access Control',
					},
				},
				providers: {},
			};

			renderWithProviders( <MenuWithProbes /> );

			fireEvent.click( await screen.findByText( 'Access Control' ) );

			expect( screen.getByTestId( 'current-screen' ) ).toHaveTextContent(
				'access-control'
			);
		} );
	} );

	describe( 'MenuItem component rendering', () => {
		it( 'renders all menu items from settings', async () => {
			vi.mocked( apiFetch ).mockResolvedValue( {} );

			(
				global as unknown as { wpGraphQLLogin: WpGraphQLLoginGlobal }
			 ).wpGraphQLLogin = {
				settings: {
					wpgraphql_login_settings: {
						label: 'Settings',
					},
					wpgraphql_login_access_control: {
						label: 'Access Control',
					},
				},
				providers: {},
			};

			renderWithProviders( <Menu /> );

			await waitFor( () => {
				expect( screen.getByText( 'Providers' ) ).toBeInTheDocument();
				expect( screen.getByText( 'Settings' ) ).toBeInTheDocument();
				expect(
					screen.getByText( 'Access Control' )
				).toBeInTheDocument();
			} );
		} );

		it( 'renders Docs link button', async () => {
			vi.mocked( apiFetch ).mockResolvedValue( {} );

			(
				global as unknown as { wpGraphQLLogin: WpGraphQLLoginGlobal }
			 ).wpGraphQLLogin = {
				settings: {},
				providers: {},
			};

			renderWithProviders( <Menu /> );

			await waitFor( () => {
				const docsLink = screen.getByText( 'Docs' );
				expect( docsLink ).toBeInTheDocument();
				expect( docsLink.closest( 'a' ) ).toHaveAttribute(
					'href',
					'https://github.com/AxeWP/wp-graphql-headless-login/blob/main/docs/reference/settings.md'
				);
				expect( docsLink.closest( 'a' ) ).toHaveAttribute(
					'target',
					'_blank'
				);
				expect( docsLink.closest( 'a' ) ).toHaveAttribute(
					'rel',
					'noreferrer'
				);
			} );
		} );

		it( 'active menu item has active styling', async () => {
			vi.mocked( apiFetch ).mockResolvedValue( {} );

			(
				global as unknown as { wpGraphQLLogin: WpGraphQLLoginGlobal }
			 ).wpGraphQLLogin = {
				settings: {
					wpgraphql_login_test: {
						label: 'Test',
					},
				},
				providers: {},
			};

			const { container } = renderWithProviders( <Menu /> );

			await waitFor( () => {
				const buttons = container.querySelectorAll(
					'.components-button.is-tertiary'
				);
				const providersButton = Array.from( buttons ).find(
					( button ) => button.textContent?.includes( 'Providers' )
				);

				expect( providersButton?.classList.contains( 'active' ) ).toBe(
					true
				);
			} );
		} );
	} );

	describe( 'Unsaved changes', () => {
		beforeEach( () => {
			vi.mocked( apiFetch ).mockResolvedValue( {
				wpgraphql_login_settings: { test: 'value' },
			} );

			(
				global as unknown as { wpGraphQLLogin: WpGraphQLLoginGlobal }
			 ).wpGraphQLLogin = {
				settings: {
					wpgraphql_login_settings: {
						label: 'Settings',
					},
					wpgraphql_login_access_control: {
						label: 'Access Control',
					},
				},
				providers: {},
			};
		} );

		// Opens the Settings screen and edits it.
		const renderDirtySettingsScreen = async () => {
			renderWithProviders( <MenuWithProbes /> );

			fireEvent.click( await screen.findByText( 'Settings' ) );
			fireEvent.click( screen.getByText( 'make-dirty' ) );
		};

		it( 'navigates directly when there are no unsaved changes', async () => {
			renderWithProviders( <MenuWithProbes /> );

			fireEvent.click( await screen.findByText( 'Access Control' ) );

			expect( screen.getByTestId( 'current-screen' ) ).toHaveTextContent(
				'access-control'
			);
			expect(
				screen.queryByText( /You have unsaved changes/ )
			).not.toBeInTheDocument();
		} );

		it( 'marks only the current screen as dirty', async () => {
			await renderDirtySettingsScreen();

			const indicators = screen.getAllByLabelText( 'Unsaved changes' );

			expect( indicators ).toHaveLength( 1 );
			expect( indicators[ 0 ]?.closest( 'button' ) ).toHaveTextContent(
				'Settings'
			);
		} );

		it( 'asks before leaving a screen with unsaved changes', async () => {
			await renderDirtySettingsScreen();

			fireEvent.click( screen.getByText( 'Access Control' ) );

			expect(
				screen.getByText( /You have unsaved changes/ )
			).toBeInTheDocument();
			expect( screen.getByTestId( 'current-screen' ) ).toHaveTextContent(
				'settings'
			);
		} );

		it( 'Cancel closes the modal without saving or navigating', async () => {
			await renderDirtySettingsScreen();

			fireEvent.click( screen.getByText( 'Access Control' ) );
			fireEvent.click( screen.getByText( 'Cancel' ) );

			expect(
				screen.queryByText( /You have unsaved changes/ )
			).not.toBeInTheDocument();
			expect( screen.getByTestId( 'current-screen' ) ).toHaveTextContent(
				'settings'
			);
			expect( apiFetch ).not.toHaveBeenCalledWith(
				expect.objectContaining( { method: 'POST' } )
			);
		} );

		it( 'Save and continue saves the current screen, then navigates', async () => {
			await renderDirtySettingsScreen();

			fireEvent.click( screen.getByText( 'Access Control' ) );
			fireEvent.click( screen.getByText( 'Save and continue' ) );

			await waitFor( () => {
				expect(
					screen.getByTestId( 'current-screen' )
				).toHaveTextContent( 'access-control' );
			} );

			expect( apiFetch ).toHaveBeenCalledWith(
				expect.objectContaining( {
					method: 'POST',
					data: {
						slug: 'wpgraphql_login_settings',
						values: { test: 'changed' },
					},
				} )
			);
			expect(
				screen.queryByText( /You have unsaved changes/ )
			).not.toBeInTheDocument();
		} );

		it( 'disables the menu items while saving', async () => {
			await renderDirtySettingsScreen();

			// The save never settles, so the menu stays in the saving state.
			vi.mocked( apiFetch ).mockReturnValueOnce(
				new Promise( () => {} )
			);

			fireEvent.click( screen.getByText( 'Access Control' ) );
			fireEvent.click( screen.getByText( 'Save and continue' ) );

			await waitFor( () => {
				expect(
					screen.getByText( 'Access Control' ).closest( 'button' )
				).toBeDisabled();
			} );
			expect(
				screen.getByText( 'Settings' ).closest( 'button' )
			).toBeDisabled();
		} );
	} );

	describe( 'Providers menu item (special case)', () => {
		it( 'renders providers as first menu item', async () => {
			vi.mocked( apiFetch ).mockResolvedValue( {} );

			(
				global as unknown as { wpGraphQLLogin: WpGraphQLLoginGlobal }
			 ).wpGraphQLLogin = {
				settings: {
					wpgraphql_login_test: {
						label: 'Test',
					},
					wpgraphql_login_another: {
						label: 'Another',
					},
				},
				providers: {},
			};

			const { container } = renderWithProviders( <Menu /> );

			await waitFor( () => {
				const buttons = container.querySelectorAll(
					'.components-button.is-tertiary'
				);

				expect( buttons.length ).toBeGreaterThan( 0 );
				expect( buttons[ 0 ]?.textContent ).toContain( 'Providers' );
			} );
		} );
	} );

	describe( 'Empty settings object', () => {
		it( 'handles empty settings gracefully', async () => {
			vi.mocked( apiFetch ).mockResolvedValue( {} );

			(
				global as unknown as { wpGraphQLLogin: WpGraphQLLoginGlobal }
			 ).wpGraphQLLogin = {
				settings: {},
				providers: {},
			};

			renderWithProviders( <Menu /> );

			await waitFor( () => {
				expect( screen.getByText( 'Providers' ) ).toBeInTheDocument();
				expect( screen.getByText( 'Docs' ) ).toBeInTheDocument();
			} );
		} );
	} );
} );
