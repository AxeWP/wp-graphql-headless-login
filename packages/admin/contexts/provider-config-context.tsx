import { useEntityProp } from '@wordpress/core-data';
import {
	createContext,
	useContext,
	useState,
	useCallback,
	useEffect,
	useMemo,
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
	const providers = useMemo(
		() => wpGraphQLLogin?.settings?.providers || {},
		[]
	);
	const providerKeys = Object.keys( providers );
	const firstProviderKey = providerKeys[ 0 ] || '';

	const [ activeClient, setActiveClientInternal ] = useState(
		`${ PROVIDER_PREFIX }${ firstProviderKey }`
	);

	/**
	 * Sets the active client, automatically adding the prefix if needed.
	 * Throws an error if the client doesn't exist in the provider settings.
	 */
	const setActiveClient = useCallback(
		( slug: string ) => {
			// If already has prefix, extract the base slug for validation
			const baseSlug = slug.startsWith( PROVIDER_PREFIX )
				? slug.replace( PROVIDER_PREFIX, '' )
				: slug;

			// Validate the provider exists
			if ( baseSlug && ! providers[ baseSlug ] ) {
				throw new Error( 'Client not found' );
			}

			// Always store with prefix for useEntityProp compatibility
			const prefixedSlug = slug.startsWith( PROVIDER_PREFIX )
				? slug
				: `${ PROVIDER_PREFIX }${ slug }`;

			setActiveClientInternal( prefixedSlug );
		},
		[ providers ]
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
				slug: activeClient,
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
