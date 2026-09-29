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

const PROVIDER_PREFIX = 'wpgraphql_login_provider_';

export const ProviderConfigProvider = ( { children }: PropsWithChildren ) => {
	const providerKeys = Object.keys( wpGraphQLLogin?.settings?.providers );
	let initialActive = PROVIDER_PREFIX;
	if ( providerKeys.length > 0 ) {
		const first = providerKeys[ 0 ]!;
		initialActive = first.startsWith( PROVIDER_PREFIX )
			? first
			: `${ PROVIDER_PREFIX }${ first }`;
	}

	const [ activeClient, setActiveClientInternal ] = useState( initialActive );

	/**
	 * Sets the active client, automatically adding the prefix if needed.
	 * Throws an error if the client doesn't exist in the provider settings.
	 */
	const setActiveClient = ( slug: string ) => {
		const baseSlug = slug.startsWith( PROVIDER_PREFIX )
			? slug.replace( PROVIDER_PREFIX, '' )
			: slug;

		const prefixedKey = `${ PROVIDER_PREFIX }${ baseSlug }`;

		if (
			! wpGraphQLLogin?.settings?.providers?.[ baseSlug ] &&
			! wpGraphQLLogin?.settings?.providers?.[ prefixedKey ]
		) {
			throw new Error( 'Client not found' );
		}

		// Always store the prefixed key for consistency
		setActiveClientInternal( prefixedKey );
	};

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
				// The stored slug is the bare provider slug, not the option name.
				slug: activeClient.replace( PROVIDER_PREFIX, '' ),
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

export const useClientContext = () => useContext( ProviderConfigContext );
