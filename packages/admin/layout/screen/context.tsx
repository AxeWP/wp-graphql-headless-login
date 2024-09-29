import {
	PropsWithChildren,
	useState,
	createContext,
	useEffect,
	useContext,
	startTransition,
} from 'react';
import { SCREEN_COMPONENTS, type AllowedScreens } from '@/admin/layout';

export const ScreenContext = createContext< {
	currentScreen: AllowedScreens;
	setCurrentScreen: ( screen: AllowedScreens ) => void;
} >( {
	currentScreen: 'providers',
	setCurrentScreen: () => {}, // eslint-disable-line @typescript-eslint/no-empty-function
} );

export const ScreenProvider = ( { children }: PropsWithChildren ) => {
	const [ currentScreen, setCurrentScreen ] =
		useState< AllowedScreens >( 'providers' );

	// The screen is set as a query parameter in the URL.
	useEffect( () => {
		startTransition( () => {
			const url = new URL( window.location.href );
			const screen = url.searchParams.get( 'screen' );

			if ( screen && screen in SCREEN_COMPONENTS ) {
				setCurrentScreen( screen as AllowedScreens );
			}
		} );
	}, [] );

	return (
		<ScreenContext.Provider value={ { currentScreen, setCurrentScreen } }>
			{ children }
		</ScreenContext.Provider>
	);
};

export const useCurrentScreen = () => {
	// Bail if we're not in a context.
	const context = useContext( ScreenContext );

	if ( ! context ) {
		throw new Error(
			'useCurrentScreen must be used within a ScreenProvider'
		);
	}

	return context;
};
