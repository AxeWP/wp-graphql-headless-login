import { renderHook, waitFor } from '@testing-library/react';
import {
	ScreenProvider,
	useCurrentScreen,
} from '@/admin/components/screen/context';

interface WpGraphQLLoginGlobal {
	settings: Record< string, unknown >;
	providers: Record< string, unknown >;
}

const setWpGraphQLLogin = ( value: WpGraphQLLoginGlobal ): void => {
	(
		global as unknown as { wpGraphQLLogin: WpGraphQLLoginGlobal }
	 ).wpGraphQLLogin = value;
};

describe( 'Screen Context', () => {
	describe( 'useCurrentScreen hook', () => {
		it( 'provides current screen and setter function', () => {
			const { result } = renderHook( () => useCurrentScreen(), {
				wrapper: ( { children } ) => (
					<ScreenProvider>{ children }</ScreenProvider>
				),
			} );

			expect( result.current.currentScreen ).toBe( 'providers' );
			expect( typeof result.current.setCurrentScreen ).toBe( 'function' );
		} );

		it( 'throws error when used outside ScreenProvider', () => {
			expect( () => renderHook( () => useCurrentScreen() ) ).toThrow(
				'useCurrentScreen must be used within a ScreenProvider'
			);
		} );
	} );

	describe( 'ScreenProvider behavior', () => {
		it( 'initializes with default screen "providers"', () => {
			const { result } = renderHook( () => useCurrentScreen(), {
				wrapper: ( { children } ) => (
					<ScreenProvider>{ children }</ScreenProvider>
				),
			} );

			expect( result.current.currentScreen ).toBe( 'providers' );
		} );

		it( 'reads screen from URL parameter on mount', async () => {
			setWpGraphQLLogin( {
				settings: {
					wpgraphql_login_settings: {},
					wpgraphql_login_test_screen: {},
				},
				providers: {},
			} );
			Object.defineProperty( window, 'location', {
				value: {
					href: 'http://example.com?screen=test-screen',
				},
				writable: true,
				configurable: true,
			} );

			const { result } = renderHook( () => useCurrentScreen(), {
				wrapper: ( { children } ) => (
					<ScreenProvider>{ children }</ScreenProvider>
				),
			} );

			await waitFor( () => {
				expect( result.current.currentScreen ).toBe( 'test-screen' );
			} );
		} );

		it( 'ignores screen parameter if not in allowed screens', async () => {
			setWpGraphQLLogin( {
				settings: {
					wpgraphql_login_settings: {},
				},
				providers: {},
			} );
			Object.defineProperty( window, 'location', {
				value: {
					href: 'http://example.com?screen=invalid-screen',
				},
				writable: true,
				configurable: true,
			} );

			const { result } = renderHook( () => useCurrentScreen(), {
				wrapper: ( { children } ) => (
					<ScreenProvider>{ children }</ScreenProvider>
				),
			} );

			await waitFor( () => {
				expect( result.current.currentScreen ).toBe( 'providers' );
			} );
		} );

		it( 'handles multiple screen parameters by using first value', async () => {
			// URL.searchParams.get() returns only the first value when multiple exist
			setWpGraphQLLogin( {
				settings: {
					wpgraphql_login_settings: {},
					wpgraphql_login_screen1: {},
					wpgraphql_login_screen2: {},
				},
				providers: {},
			} );
			Object.defineProperty( window, 'location', {
				value: {
					href: 'http://example.com?screen=screen1&screen=screen2',
				},
				writable: true,
				configurable: true,
			} );

			const { result } = renderHook( () => useCurrentScreen(), {
				wrapper: ( { children } ) => (
					<ScreenProvider>{ children }</ScreenProvider>
				),
			} );

			// First valid screen parameter should be used
			await waitFor( () => {
				expect( result.current.currentScreen ).toBe( 'screen1' );
			} );
		} );
	} );
} );
