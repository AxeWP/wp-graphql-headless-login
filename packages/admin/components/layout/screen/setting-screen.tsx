import { dispatch } from '@wordpress/data';
import { __, sprintf } from '@wordpress/i18n';
import { useEffect } from 'react';
import { Button, PanelBody, Spinner } from '@wordpress/components';
import { Fields } from '@/admin/components/fields';
import { useSettings } from '@/admin/contexts/settings-context';
import type { AllowedSettingKeys } from '@/admin/types';

export const SettingsScreen = ( {
	settingKey,
}: {
	settingKey: AllowedSettingKeys;
} ) => {
	const {
		settings: allSettings,
		updateSettings,
		isComplete,
		isSaving,
		errorMessage,
	} = useSettings();

	const optionsSchema = wpGraphQLLogin?.settings?.[ settingKey ] || undefined;

	const settings = allSettings?.[ settingKey ] || undefined;

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

	const save = async ( values: Record< string, unknown > ) => {
		if ( isSaving ) {
			return;
		}

		await updateSettings( {
			slug: settingKey,
			values,
		} );

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

	if ( ! settings || ! optionsSchema ) {
		return null;
	}

	return (
		<>
			<PanelBody>
				<Fields
					fields={ optionsSchema }
					values={ settings }
					setValue={ save }
					excludedProperties={ undefined }
				/>
			</PanelBody>
			<Button
				isBusy={ isSaving }
				onClick={ () => save( settings ) }
				disabled={ isSaving }
			>
				{ __( 'Save', 'wp-graphql-headless-login' ) }
				{ isSaving && <Spinner /> }
			</Button>
		</>
	);
};
