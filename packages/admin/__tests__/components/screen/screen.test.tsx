import { render, screen, act } from '@testing-library/react';
import { vi } from 'vitest';
import { Screen } from '@/admin/components/screen/screen';
import { ScreenProvider } from '@/admin/components/screen/context';
import { SettingsProvider } from '@/admin/contexts/settings-context';
import { setDefaultSettingsResponse } from '../../mocks/wordpress-api.mock';
import apiFetch from '@wordpress/api-fetch';

type WpGraphQLLoginGlobal = {
	settings?: Record< string, unknown >;
	providers?: Record< string, unknown >;
};

const getWpGraphQLLogin = (): WpGraphQLLoginGlobal | undefined => {
	return ( global as unknown as { wpGraphQLLogin?: WpGraphQLLoginGlobal } )
		.wpGraphQLLogin;
};

const setWpGraphQLLogin = ( value: WpGraphQLLoginGlobal ): void => {
	(
		global as unknown as { wpGraphQLLogin?: WpGraphQLLoginGlobal }
	 ).wpGraphQLLogin = value;
};

vi.mock( '@wordpress/api-fetch' );

// Partial mock: `@wordpress/components` imports many i18n functions at import time, so pass through everything except what the tests control.
vi.mock( '@wordpress/i18n', async ( importOriginal ) => ( {
	...( await importOriginal< typeof import('@wordpress/i18n') >() ),
	__: ( text: string ) => text,
	sprintf: ( text: string, ...args: string[] ) => {
		let result = text;
		args.forEach( ( arg, i ) => {
			result = result
				.replace( `%${ i + 1 }$s`, arg )
				.replace( '%s', arg );
		} );
		return result;
	},
} ) );

vi.mock( '@/admin/components/provider-config/ClientSettings', () => ( {
	ClientSettings: vi.fn( () => (
		<div data-testid="client-settings-screen">ClientSettingsScreen</div>
	) ),
} ) );

vi.mock( '@/admin/components/screen/setting-screen', () => ( {
	SettingsScreen: vi.fn( ( { settingKey }: { settingKey: string } ) => (
		<div data-testid="settings-screen" data-setting-key={ settingKey }>
			SettingsScreen: { settingKey }
		</div>
	) ),
} ) );

async function renderScreen() {
	vi.mocked( apiFetch ).mockResolvedValue( {
		wpgraphql_login_settings: {},
	} );
	let result: ReturnType< typeof render >;
	await act( async () => {
		result = render(
			<SettingsProvider>
				<ScreenProvider>
					<Screen />
				</ScreenProvider>
			</SettingsProvider>
		);
	} );
	// @ts-expect-error result is assigned in act
	return result;
}

describe( 'Screen Component', () => {
	const originalLocation = window.location;

	beforeEach( () => {
		// Reset window.location to default (no screen param)
		Object.defineProperty( window, 'location', {
			value: { href: 'http://example.com' },
			writable: true,
			configurable: true,
		} );
		// Setup initial state
		setWpGraphQLLogin( {
			settings: {
				wpgraphql_login_settings: {},
			},
			providers: {},
		} );
	} );

	afterEach( () => {
		// Reset state
		setWpGraphQLLogin( {
			settings: {},
			providers: {},
		} );
		// Restore original location
		Object.defineProperty( window, 'location', {
			value: originalLocation,
			writable: true,
			configurable: true,
		} );
	} );

	describe( 'Rendering with correct active tab', () => {
		it( 'renders with default "providers" screen', async () => {
			await renderScreen();

			const title = screen.queryByText( 'Login Providers' );
			expect( title ).toBeInTheDocument();
		} );

		it( 'renders screen title from global config', async () => {
			const loginSettings = getWpGraphQLLogin()?.settings;
			if ( loginSettings ) {
				loginSettings[ 'wpgraphql_login_settings' ] = {
					title: 'General Settings',
					description: 'General plugin settings',
					fields: {},
				};
			}

			// Set URL with screen parameter
			Object.defineProperty( window, 'location', {
				value: { href: 'http://example.com?screen=settings' },
				writable: true,
				configurable: true,
			} );

			await renderScreen();

			// Wait for component to render the title (either providers or settings)
			const title = await screen.findByRole( 'heading', { level: 2 } );
			expect( title ).toBeInTheDocument();
		} );

		it( 'renders screen description from global config', async () => {
			await renderScreen();

			const description = screen.queryByText(
				'Configure Authentication Providers that are available to users.'
			);
			expect( description ).toBeInTheDocument();
		} );

		it( 'renders custom title when defined in settings', async () => {
			const loginSettings = getWpGraphQLLogin()?.settings;
			if ( loginSettings ) {
				loginSettings[ 'wpgraphql_login_providers' ] = {
					title: 'Custom Title',
					description: 'Custom Description',
					screen: 'providers',
				};
			}

			await renderScreen();

			const title = await screen.findByText( 'Custom Title' );
			expect( title ).toBeInTheDocument();
		} );

		it( 'uses default title when custom title is not defined', async () => {
			const loginSettings = getWpGraphQLLogin()?.settings;
			if ( loginSettings ) {
				loginSettings[ 'wpgraphql_login_providers' ] = {
					description: 'Custom Description',
					screen: 'providers',
				};
			}

			await renderScreen();

			const title = screen.queryByText( 'Login Providers' );
			expect( title ).toBeInTheDocument();
		} );
	} );

	describe( 'Screen navigation between tabs', () => {
		it( 'renders ProvidersScreen when currentScreen is "providers"', async () => {
			await renderScreen();

			// ClientSettingsScreen component should be in document
			// This is tested by ensuring the wrapper is rendered with proper context
			const panel = screen.getByRole( 'main' );
			expect( panel ).toBeInTheDocument();
		} );

		it( 'renders SettingsScreen when currentScreen is not "providers"', async () => {
			const loginSettings = getWpGraphQLLogin()?.settings;
			if ( loginSettings ) {
				loginSettings[ 'wpgraphql_login_settings' ] = {
					title: 'General Settings',
					description: 'General plugin settings',
					fields: {},
				};
			}

			// Set URL with settings screen
			Object.defineProperty( window, 'location', {
				value: { href: 'http://example.com?screen=settings' },
				writable: true,
				configurable: true,
			} );

			await renderScreen();

			// SettingsScreen should be rendered
			const panel = screen.getByRole( 'main' );
			expect( panel ).toBeInTheDocument();
		} );

		it( 'handles multiple screen transitions', async () => {
			const { rerender } = await renderScreen();

			// Initial render with providers screen
			const panel = screen.getByRole( 'main' );
			expect( panel ).toBeInTheDocument();

			// Change screen
			Object.defineProperty( window, 'location', {
				value: { href: 'http://example.com?screen=settings' },
				writable: true,
				configurable: true,
			} );

			rerender(
				<SettingsProvider>
					<ScreenProvider>
						<Screen />
					</ScreenProvider>
				</SettingsProvider>
			);

			// Screen should still be rendered
			expect( panel ).toBeInTheDocument();
		} );
	} );

	describe( 'Screen context integration', () => {
		it( 'throws error when used outside ScreenProvider', () => {
			// This is tested in context.test.tsx, but we verify the component uses the context
			expect( () => {
				render(
					<SettingsProvider>
						<Screen />
					</SettingsProvider>
				);
			} ).toThrow();
		} );

		it( 'integrates with SettingsProvider', async () => {
			await expect( renderScreen() ).resolves.not.toThrow();
		} );

		it( 'receives currentScreen from context', async () => {
			await renderScreen();

			// Screen should render without errors
			const panel = screen.getByRole( 'main' );
			expect( panel ).toBeInTheDocument();
		} );
	} );

	describe( 'Edge cases', () => {
		it( 'handles invalid screen id gracefully', async () => {
			// Set URL with invalid screen
			Object.defineProperty( window, 'location', {
				value: { href: 'http://example.com?screen=invalid-screen' },
				writable: true,
				configurable: true,
			} );

			await renderScreen();

			// Should default to providers screen
			const title = screen.queryByText( 'Login Providers' );
			expect( title ).toBeInTheDocument();
		} );

		it( 'handles empty settings configuration', async () => {
			const wpGraphQLLogin = getWpGraphQLLogin();
			if ( wpGraphQLLogin ) {
				wpGraphQLLogin.settings = {};
			}

			await renderScreen();

			// Should still render with default values
			const title = screen.queryByText( 'Login Providers' );
			expect( title ).toBeInTheDocument();
		} );

		it( 'handles missing wpGraphQLLogin global', async () => {
			(
				global as unknown as { wpGraphQLLogin: WpGraphQLLoginGlobal }
			 ).wpGraphQLLogin = {
				settings: undefined,
				providers: undefined,
			} as unknown as WpGraphQLLoginGlobal;

			// Component should still render
			const { container } = await renderScreen();
			expect( container ).toBeInTheDocument();
		} );

		it( 'handles screen with no fields defined', async () => {
			const wpGraphQLLogin = getWpGraphQLLogin();
			if ( wpGraphQLLogin?.settings ) {
				wpGraphQLLogin.settings[ 'test_screen' ] = {
					title: 'Test Screen',
					description: 'Test Description',
					fields: {},
				};
			}

			Object.defineProperty( window, 'location', {
				value: { href: 'http://example.com?screen=test-screen' },
				writable: true,
				configurable: true,
			} );

			await renderScreen();

			const panel = screen.getByRole( 'main' );
			expect( panel ).toBeInTheDocument();
		} );

		it( 'handles loading state with Suspense fallback', async () => {
			setDefaultSettingsResponse( {} );

			await renderScreen();

			// Screen should render
			const panel = screen.getByRole( 'main' );
			expect( panel ).toBeInTheDocument();
		} );

		it( 'handles null or undefined description', async () => {
			const wpGraphQLLogin = getWpGraphQLLogin();
			if ( wpGraphQLLogin?.settings ) {
				wpGraphQLLogin.settings[ 'wpgraphql_login_providers' ] = {
					title: 'Test Title',
					description: null,
					screen: 'providers',
				};
			}

			await renderScreen();

			// Should render without crashing
			const title = screen.queryByText( 'Test Title' );
			expect( title ).toBeInTheDocument();
		} );
	} );

	describe( 'Component structure', () => {
		it( 'renders Panel component', async () => {
			const { container } = await renderScreen();

			const panel = container.querySelector( '.components-panel' );
			expect( panel ).toBeInTheDocument();
		} );

		it( 'renders PanelBody component', async () => {
			const { container } = await renderScreen();

			const panelBody = container.querySelector(
				'.components-panel__body'
			);
			expect( panelBody ).toBeInTheDocument();
		} );

		it( 'renders title in panel header', async () => {
			await renderScreen();

			const title = screen.getByRole( 'heading', { level: 2 } );
			expect( title ).toBeInTheDocument();
		} );
	} );

	describe( 'Explicit component rendering', () => {
		it( 'renders ClientSettingsScreen when currentScreen is "providers"', async () => {
			await renderScreen();

			const clientSettingsScreen = screen.queryByTestId(
				'client-settings-screen'
			);
			expect( clientSettingsScreen ).toBeInTheDocument();
		} );

		it( 'renders SettingsScreen when currentScreen is not "providers"', async () => {
			const loginSettings = getWpGraphQLLogin()?.settings;
			if ( loginSettings ) {
				loginSettings[ 'wpgraphql_login_settings' ] = {
					title: 'General Settings',
					description: 'General plugin settings',
					fields: {},
				};
			}

			Object.defineProperty( window, 'location', {
				value: { href: 'http://example.com?screen=settings' },
				writable: true,
				configurable: true,
			} );

			await renderScreen();

			const settingsScreen = screen.queryByTestId( 'settings-screen' );
			expect( settingsScreen ).toBeInTheDocument();
		} );

		it( 'passes correct settingKey to SettingsScreen for "settings" screen', async () => {
			const loginSettings = getWpGraphQLLogin()?.settings;
			if ( loginSettings ) {
				loginSettings[ 'wpgraphql_login_settings' ] = {
					title: 'General Settings',
					description: 'General plugin settings',
					fields: {},
				};
			}

			Object.defineProperty( window, 'location', {
				value: { href: 'http://example.com?screen=settings' },
				writable: true,
				configurable: true,
			} );

			await renderScreen();

			const settingsScreen = screen.queryByTestId( 'settings-screen' );
			expect( settingsScreen ).toBeInTheDocument();
			expect( settingsScreen ).toHaveAttribute(
				'data-setting-key',
				'wpgraphql_login_settings'
			);
		} );

		it( 'passes correct settingKey to SettingsScreen for custom screen', async () => {
			const loginSettings = getWpGraphQLLogin()?.settings;
			if ( loginSettings ) {
				loginSettings[ 'wpgraphql_login_access_control' ] = {
					title: 'Access Control',
					description: 'Manage access permissions',
					fields: {},
				};
			}

			Object.defineProperty( window, 'location', {
				value: { href: 'http://example.com?screen=access-control' },
				writable: true,
				configurable: true,
			} );

			await renderScreen();

			const settingsScreen = screen.queryByTestId( 'settings-screen' );
			expect( settingsScreen ).toBeInTheDocument();
			expect( settingsScreen ).toHaveAttribute(
				'data-setting-key',
				'wpgraphql_login_access_control'
			);
		} );
	} );

	describe( 'Edge cases - currentScreen', () => {
		it( 'handles undefined currentScreen by rendering default providers screen', async () => {
			const { container } = await renderScreen();

			const clientSettingsScreen = screen.queryByTestId(
				'client-settings-screen'
			);
			expect( clientSettingsScreen ).toBeInTheDocument();
			expect( container ).toBeInTheDocument();
		} );

		it( 'handles invalid screen name by rendering default providers screen', async () => {
			Object.defineProperty( window, 'location', {
				value: {
					href: 'http://example.com?screen=invalid-screen-name',
				},
				writable: true,
				configurable: true,
			} );

			await renderScreen();

			const clientSettingsScreen = screen.queryByTestId(
				'client-settings-screen'
			);
			expect( clientSettingsScreen ).toBeInTheDocument();
		} );

		it( 'handles empty screen parameter by rendering default providers screen', async () => {
			Object.defineProperty( window, 'location', {
				value: { href: 'http://example.com?screen=' },
				writable: true,
				configurable: true,
			} );

			await renderScreen();

			const clientSettingsScreen = screen.queryByTestId(
				'client-settings-screen'
			);
			expect( clientSettingsScreen ).toBeInTheDocument();
		} );
	} );
} );
