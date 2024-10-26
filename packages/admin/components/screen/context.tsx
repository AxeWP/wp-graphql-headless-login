import {
	PropsWithChildren,
	useState,
	createContext,
	useEffect,
	useContext,
	startTransition,
} from 'react';
import { isAllowedScreen } from './utils';

const ScreenContext = createContext< {
	currentScreen: string;
	setCurrentScreen: ( screen: string ) => void;
} >( {
	currentScreen: 'providers',
	setCurrentScreen: () => {}, // eslint-disable-line @typescript-eslint/no-empty-function
} );

export const ScreenProvider = ( { children }: PropsWithChildren ) => {
	const [ currentScreen, setCurrentScreen ] =
		useState< string >( 'providers' );

	// The screen is set as a query parameter in the URL.
	useEffect( () => {
		startTransition( () => {
			const url = new URL( window.location.href );
			const screen = url.searchParams.get( 'screen' );

			// Allowed screens appear in the WPGraphQLLogin.settings global.
			if ( screen && isAllowedScreen( screen ) ) {
				setCurrentScreen( screen );
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
