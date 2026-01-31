import { useEntityProp } from '@wordpress/core-data';
import {
	createContext,
	useContext,
	useState,
	useCallback,
	useEffect,
	type PropsWithChildren,
} from 'react';
import type {
	ClientOptionsType,
	LoginOptionsType,
	ProviderSettingType,
} from '@/admin/types';

const clientDefaults: ProviderSettingType = {
	name: '',
	order: 0,
	isEnabled: false,
	clientOptions: {} as ClientOptionsType,
	loginOptions: {
		useAuthenticationCookie: false,
	} satisfies LoginOptionsType,
};

type ProviderConfigContextType = {
	activeClient: string;
	clientConfig?: ProviderSettingType;
	setClientConfig: ( value: ProviderSettingType ) => void;
	updateClient: ( key: string, value: unknown ) => void;
	setClientOption: ( value: ClientOptionsType ) => void;
	setLoginOption: ( value: LoginOptionsType ) => void;
	setActiveClient: ( value: string ) => void;
};

const ProviderConfigContext = createContext< ProviderConfigContextType >( {
	activeClient: '',
	setActiveClient: () => {},
	updateClient: () => {},
	setClientOption: () => {},
	setLoginOption: () => {},
	setClientConfig: () => {},
} );

export const ProviderConfigProvider = ( { children }: PropsWithChildren ) => {
	const providers = wpGraphQLLogin?.settings?.providers || {};
	const [ activeClient, setActiveClient ] = useState(
		`wpgraphql_login_provider_${ Object.keys( providers )?.[ 0 ] || '' }`
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
		<ProviderConfigContext.Provider
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
		</ProviderConfigContext.Provider>
	);
};

export const useClientContext = () => {
	const context = useContext( ProviderConfigContext );

	if ( context === undefined ) {
		throw new Error(
			'useClientContext must be used within a ProviderConfigProvider'
		);
	}

	return context;
};
