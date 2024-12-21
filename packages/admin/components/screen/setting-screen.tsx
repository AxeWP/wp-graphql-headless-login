import { dispatch } from '@wordpress/data';
import { __, sprintf } from '@wordpress/i18n';
import { useEffect } from 'react';
import { Button, PanelBody, Spinner } from '@wordpress/components';
import { Fields } from '@/admin/components/fields';
import { useSettings } from '@/admin/contexts/settings-context';

export const SettingsScreen = ( { settingKey }: { settingKey: string } ) => {
	const {
		settings,
		updateSettings,
		saveSettings,
		isComplete,
		isSaving,
		isDirty,
		errorMessage,
		isConditionMet,
	} = useSettings();

	const optionsSchema =
		wpGraphQLLogin?.settings?.[ settingKey ]?.fields || undefined;

	const localValues = settings?.[ settingKey ] || {};

	useEffect( () => {
		if ( errorMessage && ! isSaving ) {
			// @ts-expect-error this isnt typed.
			dispatch( 'core/notices' ).createErrorNotice(
				sprintf(
					// translators: %s: Error message.
					__(
						'Error saving settings: %s',
						'wp-graphql-headless-login'
					),
					errorMessage
				),
				{
					type: 'snackbar',
					isDismissible: true,
				}
			);
		}
	}, [ errorMessage, isSaving ] );

	const save = async () => {
		// Prevent multiple save requests
		if ( isSaving ) {
			return;
		}

		await saveSettings( settingKey );

		if ( isComplete && ! errorMessage ) {
			// @ts-expect-error this isnt typed.
			dispatch( 'core/notices' ).createNotice(
				'success',
				__( 'Settings saved', 'wp-graphql-headless-login' ),
				{
					type: 'snackbar',
					isDismissible: true,
				}
			);
		}
	};

	const setValue = ( value: Record< string, unknown > ) => {
		updateSettings( {
			slug: settingKey,
			values: value,
		} );
	};

	const validateConditionalLogic = ( field: string ) => {
		return isConditionMet( {
			settingKey,
			field,
		} );
	};

	if ( ! settings || ! optionsSchema ) {
		return null;
	}

	return (
		<>
			<PanelBody>
				<Fields
					fields={ optionsSchema }
					values={ localValues }
					setValue={ setValue }
					excludedProperties={ undefined }
					validateConditionalLogic={ validateConditionalLogic }
				/>
			</PanelBody>
			<Button
				isBusy={ isSaving }
				onClick={ save }
				disabled={ ! isDirty || isSaving }
				variant="primary"
			>
				{ __( 'Save', 'wp-graphql-headless-login' ) }
				{ isSaving && <Spinner /> }
			</Button>
		</>
	);
};
