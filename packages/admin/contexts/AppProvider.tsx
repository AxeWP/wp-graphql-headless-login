import { useEntityProp, store } from '@wordpress/core-data';
import { useDispatch } from '@wordpress/data';
import {
	createContext,
	useContext,
	useCallback,
	useEffect,
} from '@wordpress/element';
import type { PropsWithChildren } from 'react';
import type { AccessControlSettingsType } from '../types';

const accessControlDefaults = {
	hasAccessControlAllowCredentials: false,
	hasSiteAddressInOrigin: false,
	additionalAuthorizedDomains: [],
	shouldBlockUnauthorizedDomains: false,
	customHeaders: [],
} satisfies AccessControlSettingsType;

export const AppProviderContext = createContext< {
	showAdvancedSettings: boolean;
	setShowAdvancedSettings: ( value: boolean ) => void;
	accessControlSettings: AccessControlSettingsType;
	updateAccessControlSettings: ( value: AccessControlSettingsType ) => void;
} >( {
	showAdvancedSettings: false,
	setShowAdvancedSettings: () => {}, // eslint-disable-line @typescript-eslint/no-empty-function
	accessControlSettings: {},
	updateAccessControlSettings: () => {}, // eslint-disable-line @typescript-eslint/no-empty-function
} );

export const AppProvider = ( { children }: PropsWithChildren ) => {
	const { saveEditedEntityRecord } = useDispatch( store );

	const [ shouldShow, setShouldShow ] = useEntityProp(
		'root',
		'site',
		'wpgraphql_login_settings_show_advanced_settings'
	);

	const setShowAdvancedSettings = ( value: boolean ) => {
		setShouldShow( !! value );
		saveEditedEntityRecord( 'root', 'site', undefined, {
			wpgraphql_login_settings_show_advanced_settings: !! value,
		} );
	};

	const [ accessControlSettings, setAccessControlSettings ] = useEntityProp(
		'root',
		'site',
		'wpgraphql_login_access_control'
	);

	const updateAccessControlSettings = useCallback(
		( value: AccessControlSettingsType ) => {
			setAccessControlSettings( {
				...accessControlSettings,
				...value,
			} );
		},
		[ setAccessControlSettings, accessControlSettings ]
	);

	useEffect( () => {
		if (
			undefined !== accessControlSettings &&
			Object.keys( accessControlSettings || {} )?.length === 0
		) {
			setAccessControlSettings( accessControlDefaults );
		}
	}, [ accessControlSettings, setAccessControlSettings ] );

	return (
		<AppProviderContext.Provider
			value={ {
				showAdvancedSettings: !! shouldShow,
				setShowAdvancedSettings,
				accessControlSettings,
				updateAccessControlSettings,
			} }
		>
			{ children }
		</AppProviderContext.Provider>
	);
};

export const useAppContext = () => useContext( AppProviderContext );
