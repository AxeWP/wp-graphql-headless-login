import {
	createContext,
	PropsWithChildren,
	useContext,
	useEffect,
	useState,
} from 'react';
import apiFetch from '@wordpress/api-fetch';
import type { AllowedSettingKeys } from '@/admin/types';

const REST_ENDPOINT = 'wp-graphql-login/v1/settings';

type AllowedStatuses = 'saving' | 'complete' | undefined;

type SettingType = Record< AllowedSettingKeys, Record< string, unknown > >;

export const SettingsContext = createContext< {
	settings: SettingType | undefined;
	updateSettings: ( args: {
		slug: keyof SettingType;
		values: SettingType[ keyof SettingType ];
	} ) => Promise< boolean >;
	isSaving: boolean;
	isComplete: boolean;
	errorMessage: string | undefined;
	showAdvancedSettings: boolean;
} >( {
	settings: undefined,
	updateSettings: async () => false,
	isSaving: false,
	isComplete: false,
	errorMessage: undefined,
	showAdvancedSettings: false,
} );

export const SettingsProvider = ( { children }: PropsWithChildren ) => {
	const [ status, setStatus ] = useState< AllowedStatuses >( undefined );
	const [ errorMessage, setErrorMessage ] = useState< string | undefined >();

	const [ settings, setSettings ] = useState< SettingType | undefined >(
		undefined
	);

	// Fetch settings from the REST API
	useEffect( () => {
		try {
			apiFetch< SettingType >( {
				path: REST_ENDPOINT,
			} ).then( ( response ) => {
				setSettings( response );
			} );
		} catch ( error ) {
			if ( error instanceof Error ) {
				setErrorMessage( error.message );
			}
		} finally {
			setStatus( 'complete' );
		}
	}, [] );

	const updateSettings = async ( {
		slug,
		values,
	}: {
		slug: keyof SettingType;
		values: SettingType[ keyof SettingType ];
	} ) => {
		setStatus( 'saving' );

		try {
			const response = await apiFetch< SettingType >( {
				path: REST_ENDPOINT,
				method: 'POST',
				data: {
					slug,
					values,
				},
			} );
			setSettings( response );
			setErrorMessage( undefined );
			setStatus( 'complete' );

			return true;
		} catch ( error ) {
			if ( error instanceof Error ) {
				setErrorMessage( error.message );
			}

			setStatus( 'complete' );
			return false;
		}
	};

	return (
		<SettingsContext.Provider
			value={ {
				settings,
				updateSettings,
				isSaving: status === 'saving',
				isComplete: status === 'complete',
				errorMessage,
				showAdvancedSettings:
					!! settings?.wpgraphql_login_settings
						?.show_advanced_settings,
			} }
		>
			{ children }
		</SettingsContext.Provider>
	);
};

export const useSettings = () => {
	if ( ! SettingsContext ) {
		throw new Error( 'useSettings must be used within a SettingsProvider' );
	}

	return useContext( SettingsContext );
};
