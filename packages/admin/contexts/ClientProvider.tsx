import { useEntityProp } from '@wordpress/core-data';
import {
	createContext,
	useContext,
	useState,
	useCallback,
	useEffect,
} from '@wordpress/element';
import type { PropsWithChildren } from 'react';
import type {
	ClientOptionsType,
	LoginOptionsType,
	ProviderSettingType,
} from '../types';

const clientDefaults: ProviderSettingType = {
	name: '',
	order: 0,
	isEnabled: false,
	clientOptions: {} as ClientOptionsType,
	loginOptions: {
		useAuthenticationCookie: false,
	} satisfies LoginOptionsType,
};

export const ClientProviderContext = createContext< {
	activeClient: string;
	clientConfig?: ProviderSettingType;
	setClientConfig: ( value: ProviderSettingType ) => void;
	updateClient: ( key: string, value: unknown ) => void;
	setClientOption: ( value: ClientOptionsType ) => void;
	setLoginOption: ( value: LoginOptionsType ) => void;
	setActiveClient: ( value: string ) => void;
} >( {
	activeClient: '',
	setActiveClient: () => {}, // eslint-disable-line @typescript-eslint/no-empty-function
	clientConfig: undefined,
	setClientConfig: () => {}, // eslint-disable-line @typescript-eslint/no-empty-function
	updateClient: () => {}, // eslint-disable-line @typescript-eslint/no-empty-function
	setClientOption: () => {}, // eslint-disable-line @typescript-eslint/no-empty-function
	setLoginOption: () => {}, // eslint-disable-line @typescript-eslint/no-empty-function
} );

export const ClientProvider = ( { children }: PropsWithChildren ) => {
	const [ activeClient, setActiveClient ] = useState(
		Object.keys( wpGraphQLLogin?.settings.providers )?.[ 0 ] || ''
	);

	const [ clientConfig, setClientConfig ] = useEntityProp(
		'root',
		'site',
		activeClient
	);

	const updateClient = useCallback(
		( key: string, value: unknown ) => {
			const newConfig = {
				...clientConfig,
				[ key ]: value,
			};
			setClientConfig( newConfig );
		},
		[ clientConfig, setClientConfig ]
	);

	const setClientOption = useCallback(
		( clientOption: object ) => {
			updateClient( 'clientOptions', {
				...clientConfig?.clientOptions,
				...clientOption,
			} );
		},
		[ clientConfig, updateClient ]
	);

	const setLoginOption = useCallback(
		( loginOption: object ) => {
			updateClient( 'loginOptions', {
				...clientConfig?.loginOptions,
				...loginOption,
			} );
		},
		[ clientConfig, updateClient ]
	);

	useEffect( () => {
		if (
			undefined !== activeClient &&
			undefined !== clientConfig &&
			Object.keys( clientConfig || {} )?.length === 0
		) {
			setClientConfig( {
				...clientDefaults,
				slug: activeClient.replace( 'wpgraphql_login_provider_', '' ),
			} );
		}
	}, [ clientConfig, setClientConfig, activeClient ] );

	return (
		<ClientProviderContext.Provider
			value={ {
				activeClient,
				setActiveClient,
				clientConfig,
				setClientConfig: setClientConfig as (
					value: ProviderSettingType
				) => void,
				updateClient,
				setClientOption,
				setLoginOption,
			} }
		>
			{ children }
		</ClientProviderContext.Provider>
	);
};

export const useClientContext = () => useContext( ClientProviderContext );
