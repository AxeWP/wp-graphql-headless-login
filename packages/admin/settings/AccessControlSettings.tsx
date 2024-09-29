import { useEffect } from '@wordpress/element';
import { Button, PanelBody, Spinner } from '@wordpress/components';
import { sprintf, __ } from '@wordpress/i18n';
import { store as coreStore } from '@wordpress/core-data';
import { useDispatch, dispatch, useSelect } from '@wordpress/data';
import { useAppContext } from '../contexts/AppProvider';
import { Fields } from '../components/fields';

function AccessControlSettings() {
	const { accessControlSettings, updateAccessControlSettings } =
		useAppContext();

	const { saveEditedEntityRecord } = useDispatch( coreStore );

	const { lastError, isSaving, hasEdits } = useSelect(
		( select ) => ( {
			// @ts-expect-error this isnt typed.
			lastError: select( coreStore )?.getLastEntitySaveError(
				'root',
				'site'
			),
			// @ts-expect-error this isnt typed.
			isSaving: select( coreStore )?.isSavingEntityRecord(
				'root',
				'site'
			),
			// @ts-expect-error this isnt typed.
			hasEdits: select( coreStore )?.hasEditsForEntityRecord(
				'root',
				'site'
			),
		} ),
		[]
	);

	const excludedProperties = [] satisfies string[];

	const optionsSchema = wpGraphQLLogin?.settings?.accessControl || {};

	useEffect( () => {
		if ( lastError ) {
			// @ts-expect-error this isnt typed.
			dispatch( 'core/notices' ).createErrorNotice(
				sprintf(
					// translators: %s: Error message.
					__(
						'Error saving settings: %s',
						'wp-graphql-headless-login'
					),
					lastError?.message
				),
				{
					type: 'snackbar',
					isDismissible: true,
				}
			);
		}
	}, [ lastError ] );

	const saveRecord = async () => {
		const saved = await saveEditedEntityRecord( 'root', 'site', undefined, {
			wpgraphql_login_access_control: accessControlSettings,
		} );

		if ( saved ) {
			// @ts-expect-error this isnt typed.
			dispatch( 'core/notices' ).createNotice(
				'success',
				'Settings saved',
				{
					type: 'snackbar',
					isDismissible: true,
				}
			);
		}
	};

	return (
		<>
			<PanelBody>
				<Fields
					fields={ optionsSchema }
					values={ accessControlSettings }
					setValue={ updateAccessControlSettings }
					excludedProperties={ excludedProperties }
				/>
			</PanelBody>
			<Button
				variant="primary"
				isPrimary
				disabled={ ! hasEdits }
				isBusy={ isSaving }
				onClick={ () => {
					saveRecord();
				} }
			>
				{ __(
					'Save Access Control Settings',
					'wp-graphql-headless-login'
				) }
				{ isSaving && <Spinner /> }
			</Button>
		</>
	);
}

export default AccessControlSettings;
