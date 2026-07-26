import {
	createContext,
	useContext,
	useEffect,
	useState,
	type PropsWithChildren,
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
	saveSettings: (
		slug: keyof SettingType,
		valuesOverride?: Record< string, unknown >
	) => Promise< boolean >;
	isConditionMet: ( {
		settingKey,
		field,
	}: {
		settingKey: string;
		field: string;
	} ) => boolean;
	getUnmetCondition: ( {
		settingKey,
		field,
	}: {
		settingKey: string;
		field: string;
	} ) => { settingKey: string; field: string } | undefined;
	isComplete: boolean;
	isDirty: boolean;
	isSaving: boolean;
	errorMessage: string | undefined;
	showAdvancedSettings: boolean;
} >( {
	isConditionMet: () => true,
	getUnmetCondition: () => undefined,
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
		!! settings?.[ 'wpgraphql_login_settings' ]?.[
			'show_advanced_settings'
		];

	// Fetch settings from the REST API
	useEffect( () => {
		apiFetch< SettingType >( {
			path: REST_ENDPOINT,
		} )
			.then( ( response ) => {
				setServerSettings( response );
				setSettings( response ); // Initialize settings
			} )
			.catch( ( error: unknown ) => {
				if ( error instanceof Error ) {
					setErrorMessage( error.message );
				} else {
					setErrorMessage(
						__(
							'Unable to fetch settings. An unknown error occurred',
							'wp-graphql-headless-login'
						)
					);
				}
			} )
			.finally( () => {
				setStatus( 'complete' );
			} );
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
		slug: keyof SettingType,
		valuesOverride?: Record< string, unknown >
	): Promise< boolean > => {
		setStatus( 'saving' );
		try {
			const response = await apiFetch< SettingType >( {
				path: REST_ENDPOINT,
				method: 'POST',
				data: {
					slug,
					values: valuesOverride ?? settings?.[ slug ],
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
	 * Finds the field blocking a setting from being displayed, if there is one.
	 *
	 * When a rule's target is itself blocked, the root cause is returned so
	 * callers can point the user at the setting they actually need to change.
	 */
	const getUnmetCondition = ( {
		settingKey,
		field,
	}: {
		settingKey: string;
		field: string;
	} ): { settingKey: string; field: string } | undefined => {
		// Get the logic rule.
		const conditionalLogic =
			wpGraphQLLogin?.settings?.[ settingKey ]?.fields?.[ field ]
				?.conditionalLogic;

		if ( ! conditionalLogic ) {
			return undefined;
		}

		const conditionalLogicArray = Array.isArray( conditionalLogic )
			? conditionalLogic
			: [ conditionalLogic ];

		// Check if the condition is met by comparing the current field value to the rule.
		for ( const rule of conditionalLogicArray ) {
			const { slug, operator, value } = rule;

			// Parse the slug to get the setting and field. If there is no dot, the field is on the current setting.
			const [ targetSetting, targetField ] = slug.includes( '.' )
				? slug.split( '.' )
				: [ settingKey, slug ];

			if ( ! targetSetting || ! targetField ) {
				return { settingKey, field };
			}

			const target = { settingKey: targetSetting, field: targetField };

			const fieldValue = settings?.[ targetSetting as string ]?.[
				targetField
			] as string | undefined;

			if ( ! fieldValue ) {
				return target;
			}

			// If the field schema has a condition, we need to check if the condition is met.
			const unmetParent = wpGraphQLLogin?.settings?.[ targetSetting ]
				?.fields?.[ targetField ]?.conditionalLogic
				? getUnmetCondition( target )
				: undefined;

			if ( unmetParent ) {
				return unmetParent;
			}

			let isMet: boolean;

			switch ( operator ) {
				case '==':
					isMet = fieldValue === value;
					break;
				case '!=':
					isMet = fieldValue !== value;
					break;
				case '>':
					isMet = fieldValue > value;
					break;
				case '<':
					isMet = fieldValue < value;
					break;
				case '>=':
					isMet = fieldValue >= value;
					break;
				case '<=':
					isMet = fieldValue <= value;
					break;
				default:
					isMet = true;
			}

			if ( ! isMet ) {
				return target;
			}
		}

		return undefined;
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
	} ) => ! getUnmetCondition( { settingKey, field } );

	return (
		<SettingsContext.Provider
			value={ {
				settings,
				isConditionMet,
				getUnmetCondition,
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
	const contextValue = useContext( SettingsContext );
	if ( ! contextValue ) {
		throw new Error( 'useSettings must be used within a SettingsProvider' );
	}

	return useContext( SettingsContext );
};
