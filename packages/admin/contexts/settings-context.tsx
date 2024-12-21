import {
	createContext,
	PropsWithChildren,
	useContext,
	useEffect,
	useState,
} from 'react';
import apiFetch from '@wordpress/api-fetch';

const REST_ENDPOINT = 'wp-graphql-login/v1/settings';

type AllowedStatuses = 'saving' | 'complete' | undefined;
type SettingType = Record< string, Record< string, unknown > >;

const SettingsContext = createContext< {
	settings: SettingType | undefined;
	updateSettings: ( {
		slug,
		values,
	}: {
		slug: keyof SettingType;
		values: Record< string, unknown >;
	} ) => void;
	resetSettings: () => void;
	saveSettings: ( slug: keyof SettingType ) => Promise< boolean >;
	isComplete: boolean;
	isDirty: boolean;
	isSaving: boolean;
	errorMessage: string | undefined;
	showAdvancedSettings: boolean;
} >( {
	settings: undefined,
	updateSettings: () => {},
	resetSettings: () => {},
	saveSettings: async () => false,
	isDirty: false,
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

	// Cached server state. This is used to determine if the settings are dirty
	const [ serverSettings, setServerSettings ] = useState<
		SettingType | undefined
	>( undefined );

	const isDirty =
		( settings &&
			JSON.stringify( settings ) !== JSON.stringify( serverSettings ) ) ||
		false;
	const isSaving = status === 'saving';
	const isComplete = status === 'complete';
	const showAdvancedSettings =
		!! settings?.wpgraphql_login_settings?.show_advanced_settings;

	// Fetch settings from the REST API
	useEffect( () => {
		try {
			apiFetch< SettingType >( {
				path: REST_ENDPOINT,
			} ).then( ( response ) => {
				setServerSettings( response );
				setSettings( response ); // Initialize settings
			} );
		} catch ( error ) {
			if ( error instanceof Error ) {
				setErrorMessage( error.message );
			}
		} finally {
			setStatus( 'complete' );
		}
	}, [] );

	/**
	 * Update the settings state with new values
	 */
	const updateSettings = ( {
		slug,
		values,
	}: {
		slug: keyof SettingType;
		values: Record< string, unknown >;
	} ) => {
		setSettings( ( prevSettings ) => {
			if ( ! prevSettings ) {
				return {
					[ slug ]: values,
				};
			}

			return {
				...prevSettings,
				[ slug ]: values,
			};
		} );
	};

	/**
	 * Reset the settings to the server state
	 */
	const resetSettings = () => {
		setSettings( serverSettings );
	};

	/**
	 * Save the settings to the REST API
	 */
	const saveSettings = async (
		slug: keyof SettingType
	): Promise< boolean > => {
		setStatus( 'saving' );
		try {
			const response = await apiFetch< SettingType >( {
				path: REST_ENDPOINT,
				method: 'POST',
				data: {
					slug,
					values: settings?.[ slug ],
				},
			} );
			setServerSettings( response );
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
				saveSettings,
				resetSettings,
				isComplete,
				isDirty,
				isSaving,
				errorMessage,
				showAdvancedSettings,
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
