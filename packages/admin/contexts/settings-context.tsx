import {
	createContext,
	PropsWithChildren,
	useContext,
	useEffect,
	useState,
} from 'react';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

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
	saveSettings: ( slug: keyof SettingType ) => Promise< boolean >;
	isConditionMet: ( {
		settingKey,
		field,
	}: {
		settingKey: string;
		field: string;
	} ) => boolean;
	isComplete: boolean;
	isDirty: boolean;
	isSaving: boolean;
	errorMessage: string | undefined;
	showAdvancedSettings: boolean;
} >( {
	isConditionMet: () => true,
	settings: undefined,
	updateSettings: () => {},
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
			} else {
				setErrorMessage(
					__(
						'Unable to save settings. An unknown error occurred',
						'wp-graphql-headless-login'
					)
				);
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

	/**
	 * Checks whether the condition for a field is met.
	 */
	const isConditionMet = ( {
		settingKey,
		field,
	}: {
		settingKey: string;
		field: string;
	} ) => {
		// Get the logic rule.
		const conditionalLogic =
			wpGraphQLLogin?.settings?.[ settingKey ]?.fields?.[ field ]
				?.conditionalLogic;

		if ( ! conditionalLogic ) {
			return true;
		}

		const conditionalLogicArray = Array.isArray( conditionalLogic )
			? conditionalLogic
			: [ conditionalLogic ];

		// Check if the condition is met by comparing the current field value to the rule.
		return conditionalLogicArray.every( ( rule ) => {
			const { slug, operator, value } = rule;

			// Parse the slug to get the setting and field. If there is no dot, the field is on the current setting.
			const [ targetSetting, targetField ] = slug.includes( '.' )
				? slug.split( '.' )
				: [ settingKey, slug ];
			const fieldValue = settings?.[ targetSetting ]?.[ targetField ];

			if ( ! fieldValue ) {
				return false;
			}

			// If the field schema has a condition, we need to check if the condition is met.
			const isParentConditionMet = wpGraphQLLogin?.settings?.[
				targetSetting
			]?.fields?.[ targetField ]?.conditionalLogic
				? isConditionMet( {
						settingKey: targetSetting,
						field: targetField,
				  } )
				: true;

			if ( ! isParentConditionMet ) {
				return false;
			}

			switch ( operator ) {
				case '==':
					return fieldValue === value;
				case '!=':
					return fieldValue !== value;
				case '>':
					return fieldValue > value;
				case '<':
					return fieldValue < value;
				case '>=':
					return fieldValue >= value;
				case '<=':
					return fieldValue <= value;
				default:
					return true;
			}
		} );
	};

	return (
		<SettingsContext.Provider
			value={ {
				settings,
				isConditionMet,
				updateSettings,
				saveSettings,
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
