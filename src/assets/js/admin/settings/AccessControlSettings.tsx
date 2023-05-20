/**
 * External Dependencies
 */
import { useEffect } from '@wordpress/element';
import { Button, PanelBody, PanelRow } from '@wordpress/components';
import { sprintf, __ } from '@wordpress/i18n';
import { store as coreStore, useEntityProp } from '@wordpress/core-data';
import { useDispatch, dispatch, useSelect } from '@wordpress/data';

/**
 * Internal Dependencies.
 */
import { OptionList } from '../components';

interface AccessControlSettings {
	hasAccessControlAllowCredentials: boolean;
	hasSiteAddressInOrigin: boolean;
	additionalAuthorizedDomains: string[];
	shouldBlockUnauthorizedDomains: boolean;
	customHeaders: string[];
}

const accessControlDefaults: AccessControlSettings = {
	hasAccessControlAllowCredentials: false,
	hasSiteAddressInOrigin: false,
	additionalAuthorizedDomains: [],
	shouldBlockUnauthorizedDomains: false,
	customHeaders: [],

export type AccessControlEntityProps = [
	AccessControlSettings,
	React.Dispatch<React.SetStateAction<AccessControlSettings>>
];

export function AccessControlSettings({ showAdvancedSettings }) {
	const { saveEditedEntityRecord } = useDispatch(coreStore);

	const [
		accessControlSettings,
		setAccessControlSettings,
	]: AccessControlEntityProps = useEntityProp(
		'root',
		'site',
		'wpgraphql_login_access_control'
	);

	useEffect(() => {
		if (
			undefined !== accessControlSettings &&
			Object.keys(accessControlSettings || {})?.length === 0
		) {
			setAccessControlSettings(accessControlDefaults);
		}
	}, [accessControlSettings, setAccessControlSettings]);

	const { lastError } = useSelect(
		(select) => ({
			lastError: select(coreStore).getLastEntitySaveError('root', 'site'),
			isSaving: select(coreStore).isSavingEntityRecord('root', 'site'),
			hasEdits: select(coreStore).hasEditsForEntityRecord('root', 'site'),
		}),
		[accessControlSettings]
	);

	const excludedProperties = [];
	const optionsSchema = wpGraphQLLogin?.settings?.accessControl || {};

	useEffect(() => {
		if (lastError) {
			dispatch('core/notices').createErrorNotice(
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
	}, [lastError]);

	const updateSettings = (value) => {
		const newAccessControlSettings = {
			...accessControlSettings,
			...value,
		};
		setAccessControlSettings(newAccessControlSettings);
	};

	const saveRecord = async () => {
		const saved = await saveEditedEntityRecord('root', 'site', undefined, {
			wpgraphql_login_access_control: accessControlSettings,
		});

		if (saved) {
			dispatch('core/notices').createNotice('success', 'Settings saved', {
				type: 'snackbar',
				isDismissible: true,
			});
		}
	};

	return (
		<>
			<PanelBody>
				<PanelRow>
					<h2 className="components-panel__body-title">
						{__(
							'Access Control Settings',
							'wp-graphql-headless-login'
						)}
					</h2>
				</PanelRow>
				<OptionList
					optionsSchema={optionsSchema}
					options={accessControlSettings}
					setOption={updateSettings}
					showAdvancedSettings={showAdvancedSettings}
					excludedProperties={excludedProperties}
				/>
			</PanelBody>
			<Button
				isPrimary
				onClick={() => {
					saveRecord();
				}}
			>
				{__(
					'Save Access Control Settings',
					'wp-graphql-headless-login'
				)}
			</Button>
		</>
	);
}
