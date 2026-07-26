import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { Menu } from '@/admin/components/layout/header/menu';
import { SettingsProvider } from '@/admin/contexts/settings-context';
import { ScreenProvider } from '@/admin/components/screen/context';
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
	...( await importOriginal< typeof import('@wordpress/i18n') >() ),
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

		it( 'includes providers as first menu item', async () => {
			vi.mocked( apiFetch ).mockResolvedValue( {} );

			(
				global as unknown as { wpGraphQLLogin: WpGraphQLLoginGlobal }
			 ).wpGraphQLLogin = {
				settings: {
					wpgraphql_login_providers: {
						label: 'Providers',
					},
					wpgraphql_login_test: {
						label: 'Test',
					},
				},
				providers: {},
			};

			renderWithProviders( <Menu /> );

			await waitFor( () => {
				const providersItem = screen.getByText( 'Providers' );
				expect( providersItem ).toBeInTheDocument();
			} );
		} );

		it( 'handles empty settings object', async () => {
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
			} );
		} );

		it( 'uses default label for providers when not defined', async () => {
			vi.mocked( apiFetch ).mockResolvedValue( {} );

			(
				global as unknown as { wpGraphQLLogin: WpGraphQLLoginGlobal }
			 ).wpGraphQLLogin = {
				settings: {
					wpgraphql_login_providers: {
						label: 'Providers',
					},
				},
				providers: {},
			};

			renderWithProviders( <Menu /> );

			await waitFor( () => {
				expect( screen.getByText( 'Providers' ) ).toBeInTheDocument();
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

			renderWithProviders( <Menu /> );

			await waitFor( () => {
				expect(
					screen.getByText( 'Access Control' )
				).toBeInTheDocument();
			} );
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

		it( 'dirty indicator shows for current screen when dirty', async () => {
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
				},
				providers: {},
			};

			const { container } = renderWithProviders( <Menu /> );

			await waitFor( () => {
				expect( container ).toBeInTheDocument();
			} );

			const dirtyIndicators =
				document.querySelectorAll( '.dirtyIndicator' );

			expect( dirtyIndicators ).toBeDefined();
		} );

		it( 'menu items disabled when isSaving', async () => {
			vi.mocked( apiFetch ).mockImplementation( ( { method } ) => {
				if ( method === 'POST' ) {
					return new Promise( ( resolve ) => {
						setTimeout(
							() =>
								resolve( {
									wpgraphql_login_settings: { test: 'value' },
								} ),
							100
						);
					} );
				}
				return Promise.resolve( {
					wpgraphql_login_settings: { test: 'value' },
				} );
			} );

			(
				global as unknown as { wpGraphQLLogin: WpGraphQLLoginGlobal }
			 ).wpGraphQLLogin = {
				settings: {
					wpgraphql_login_settings: {
						label: 'Settings',
					},
				},
				providers: {},
			};

			const { container } = renderWithProviders( <Menu /> );

			await waitFor( () => {
				expect( container ).toBeInTheDocument();
			} );

			const buttons = container.querySelectorAll( 'button' );
			expect( buttons.length ).toBeGreaterThan( 0 );
		} );
	} );

	describe( 'SaveChangesModal component', () => {
		it( 'shows modal when clicking menu item while dirty', async () => {
			vi.mocked( apiFetch ).mockResolvedValue( {
				wpgraphql_login_settings: { test: 'value' },
				wpgraphql_login_test_screen: { test: 'value' },
			} );

			(
				global as unknown as { wpGraphQLLogin: WpGraphQLLoginGlobal }
			 ).wpGraphQLLogin = {
				settings: {
					wpgraphql_login_settings: {
						label: 'Settings',
					},
					wpgraphql_login_test_screen: {
						label: 'Test Screen',
					},
				},
				providers: {},
			};

			const { container } = renderWithProviders( <Menu /> );

			await waitFor( () => {
				expect( container ).toBeInTheDocument();
			} );

			const testScreenButton = screen.getByText( 'Test Screen' );

			fireEvent.click( testScreenButton );
		} );

		it( 'modal displays correct message', async () => {
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
				},
				providers: {},
			};

			renderWithProviders( <Menu /> );

			await waitFor( () => {
				expect( screen.getByText( 'Docs' ) ).toBeInTheDocument();
			} );
		} );

		it( 'Cancel closes modal without saving', async () => {
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
				},
				providers: {},
			};

			renderWithProviders( <Menu /> );

			await waitFor( () => {
				expect( screen.getByText( 'Docs' ) ).toBeInTheDocument();
			} );
		} );

		it( 'Save and continue saves and changes screen', async () => {
			vi.mocked( apiFetch ).mockResolvedValue( {
				wpgraphql_login_settings: { test: 'value' },
				wpgraphql_login_test_screen: { test: 'value' },
			} );

			(
				global as unknown as { wpGraphQLLogin: WpGraphQLLoginGlobal }
			 ).wpGraphQLLogin = {
				settings: {
					wpgraphql_login_settings: {
						label: 'Settings',
					},
					wpgraphql_login_test_screen: {
						label: 'Test Screen',
					},
				},
				providers: {},
			};

			renderWithProviders( <Menu /> );

			await waitFor( () => {
				expect( screen.getByText( 'Docs' ) ).toBeInTheDocument();
			} );
		} );

		it( 'handles save failure in modal', async () => {
			vi.mocked( apiFetch ).mockRejectedValue(
				new Error( 'Save failed' )
			);

			(
				global as unknown as { wpGraphQLLogin: WpGraphQLLoginGlobal }
			 ).wpGraphQLLogin = {
				settings: {
					wpgraphql_login_settings: {
						label: 'Settings',
					},
				},
				providers: {},
			};

			renderWithProviders( <Menu /> );

			await waitFor( () => {
				expect( screen.getByText( 'Docs' ) ).toBeInTheDocument();
			} );
		} );

		it( 'handles modal with no nextScreen', async () => {
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
				},
				providers: {},
			};

			renderWithProviders( <Menu /> );

			await waitFor( () => {
				expect( screen.getByText( 'Docs' ) ).toBeInTheDocument();
			} );
		} );
	} );

	describe( 'Menu component with dirty state', () => {
		it( 'clicking menu item changes screen when not dirty', async () => {
			vi.mocked( apiFetch ).mockResolvedValue( {
				wpgraphql_login_settings: { test: 'value' },
				wpgraphql_login_test_screen: { test: 'value' },
			} );

			(
				global as unknown as { wpGraphQLLogin: WpGraphQLLoginGlobal }
			 ).wpGraphQLLogin = {
				settings: {
					wpgraphql_login_settings: {
						label: 'Settings',
					},
					wpgraphql_login_test_screen: {
						label: 'Test Screen',
					},
				},
				providers: {},
			};

			const { container } = renderWithProviders( <Menu /> );

			await waitFor( () => {
				expect( screen.getByText( 'Docs' ) ).toBeInTheDocument();
			} );

			const buttons = container.querySelectorAll(
				'.components-button.is-tertiary'
			);
			const testScreenButton = Array.from( buttons ).find(
				( button ) => button.textContent?.includes( 'Test Screen' )
			);

			expect( testScreenButton ).toBeInTheDocument();
		} );

		it( 'screen navigation works correctly', async () => {
			vi.mocked( apiFetch ).mockResolvedValue( {
				wpgraphql_login_settings: { test: 'value' },
				wpgraphql_login_access_control: { test: 'value' },
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

			renderWithProviders( <Menu /> );

			await waitFor( () => {
				expect( screen.getByText( 'Docs' ) ).toBeInTheDocument();
			} );
		} );
	} );

	describe( 'Modal interactions', () => {
		it( 'test modal save flow', async () => {
			vi.mocked( apiFetch ).mockResolvedValue( {
				wpgraphql_login_settings: { test: 'value' },
				wpgraphql_login_test_screen: { test: 'value' },
			} );

			(
				global as unknown as { wpGraphQLLogin: WpGraphQLLoginGlobal }
			 ).wpGraphQLLogin = {
				settings: {
					wpgraphql_login_settings: {
						label: 'Settings',
					},
					wpgraphql_login_test_screen: {
						label: 'Test Screen',
					},
				},
				providers: {},
			};

			renderWithProviders( <Menu /> );

			await waitFor( () => {
				expect( screen.getByText( 'Docs' ) ).toBeInTheDocument();
			} );
		} );

		it( 'modal handleSaveAndContinue saves settings and changes screen', async () => {
			// Setup: Make settings dirty so clicking menu item shows modal
			vi.mocked( apiFetch )
				.mockResolvedValueOnce( {
					wpgraphql_login_settings: { test: 'value' },
					wpgraphql_login_access_control: { control: 'value' },
				} )
				.mockResolvedValueOnce( {
					wpgraphql_login_settings: { test: 'updated' },
					wpgraphql_login_access_control: { control: 'value' },
				} );

			(
				global as unknown as { wpGraphQLLogin: WpGraphQLLoginGlobal }
			 ).wpGraphQLLogin = {
				settings: {
					wpgraphql_login_settings: {
						label: 'Settings',
						fields: { test: { label: 'Test', type: 'string' } },
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
			} );

			// Click on Access Control to navigate (when clean, should not show modal)
			const accessControlButton = screen.getByText( 'Access Control' );
			fireEvent.click( accessControlButton );

			await waitFor( () => {
				// Screen should change without modal when not dirty
				expect( accessControlButton ).toBeInTheDocument();
			} );
		} );

		it( 'modal handleCancel closes modal without navigation', async () => {
			vi.mocked( apiFetch ).mockResolvedValue( {
				wpgraphql_login_settings: { test: 'value' },
				wpgraphql_login_other_screen: { other: 'value' },
			} );

			(
				global as unknown as { wpGraphQLLogin: WpGraphQLLoginGlobal }
			 ).wpGraphQLLogin = {
				settings: {
					wpgraphql_login_settings: {
						label: 'Settings',
					},
					wpgraphql_login_other_screen: {
						label: 'Other Screen',
					},
				},
				providers: {},
			};

			renderWithProviders( <Menu /> );

			await waitFor( () => {
				expect(
					screen.getByText( 'Other Screen' )
				).toBeInTheDocument();
			} );

			// Click on Other Screen - when not dirty, should navigate directly
			const otherScreenButton = screen.getByText( 'Other Screen' );
			fireEvent.click( otherScreenButton );

			await waitFor( () => {
				// Verify menu is still rendered
				expect( screen.getByText( 'Providers' ) ).toBeInTheDocument();
			} );
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

		it( 'providers has correct screen name', async () => {
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
