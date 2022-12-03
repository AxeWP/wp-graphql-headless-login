/**
 * External Dependencies
 */
import { useEffect } from '@wordpress/element';
import {
	Button,
	Icon,
	PanelBody,
	PanelRow,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { sprintf, __ } from '@wordpress/i18n';
import { store as coreStore, useEntityProp } from '@wordpress/core-data';
import { useDispatch, dispatch, useSelect } from '@wordpress/data';

/**
 * Internal Dependencies.
 */
import { OptionSettings } from './OptionSettings';

const clientDefaults: ClientType = {
	name: 'New client',
	order: 0,
	isEnabled: false,
	clientOptions: {
		clientId: '',
		clientSecret: '',
		redirectUri: '',
	},
	loginOptions: {
		createUserIfNoneExists: true,
	},
};

export interface ClientType {
	id: string;
	name: string;
	order: int;
	isEnabled: boolean;
	clientOptions: object;
	loginOptions: LoginOptionSetttingsType;
}

export function ClientSettings({ clientSlug }) {
	const { saveEditedEntityRecord } = useDispatch(coreStore);

	const [client, setClient] = useEntityProp('root', 'site', clientSlug);

	useEffect(() => {
		if (undefined !== client && Object.keys(client || {})?.length === 0) {
			setClient({
				...clientDefaults,
				slug: clientSlug.replace('wpgraphql_login_provider_', ''),
			});
		}
	}, [client, setClient, clientSlug]);

	const { lastError } = useSelect(
		(select) => ({
			lastError: select(coreStore).getLastEntitySaveError('root', 'site'),
			isSaving: select(coreStore).isSavingEntityRecord('root', 'site'),
			hasEdits: select(coreStore).hasEditsForEntityRecord('root', 'site'),
		}),
		[client]
	);

	useEffect(() => {
		if (lastError) {
			dispatch('core/notices').createErrorNotice(
				sprintf(
					// translators: %s: Error message.
					__(
						'Error saving settings: %s',
						'wp-graphql-headless-login'
					),
					lastError?.data?.params?.[clientSlug] || lastError?.message
				),
				{
					type: 'snackbar',
					isDismissible: true,
				}
			);
		}
	}, [lastError, clientSlug]);

	const updateClient = (key: string, value: string) => {
		const newClient = {
			...client,
			[key]: value,
		};
		setClient(newClient);
	};

	const setClientOption = (clientOption: object) => {
		updateClient('clientOptions', {
			...client?.clientOptions,
			...clientOption,
		});
	};

	const setLoginOption = (loginOption: object) => {
		updateClient('loginOptions', {
			...client?.loginOptions,
			...loginOption,
		});
	};

	const saveRecord = async () => {
		const saved = await saveEditedEntityRecord('root', 'site', undefined, {
			[clientSlug]: client,
		});

		if (saved) {
			dispatch('core/notices').createNotice('success', 'Settings saved', {
				type: 'snackbar',
				isDismissible: true,
			});
		}
	};

	const CustomPanel = () => {
		return wpGraphQLLogin.hooks.applyFilters(
			'graphql_login_custom_client_settings',
			<></>,
			clientSlug,
			client
		);
	};

	return (
		<>
			<PanelBody>
				<PanelRow>
					<h2 className="components-panel__body-title">
						{sprintf(
							// translators: %s: Client slug.
							__('%s Settings.', 'wp-graphql-headless-login'),
							wpGraphQLLogin?.settings?.[clientSlug]?.title ||
								'Client'
						)}
					</h2>
				</PanelRow>
				<ToggleControl
					checked={client?.isEnabled}
					label={__('Enable Provider.', 'wp-graphql-headless-login')}
					onChange={(selected) => updateClient('isEnabled', selected)}
				/>
				<TextControl
					label={__('Client Label.', 'wp-graphql-headless-login')}
					onChange={(selected) => updateClient('name', selected)}
					value={client?.name}
					required
				/>
				<OptionSettings
					clientSlug={clientSlug}
					optionsKey="clientOptions"
					options={client?.clientOptions}
					setOption={setClientOption}
				/>
			</PanelBody>
			<PanelBody>
				<PanelRow>
					<h2 className="components-panel__body-title">
						{__('Login Settings.', 'wp-graphql-headless-login')}
						<Icon
							icon="admin-users"
							className="components-panel__icon"
							size={20}
						/>
					</h2>
				</PanelRow>

				<OptionSettings
					clientSlug={clientSlug}
					optionsKey="loginOptions"
					options={client?.loginOptions}
					setOption={setLoginOption}
				/>
			</PanelBody>

			<CustomPanel />

			<Button
				isPrimary
				onClick={() => {
					saveRecord();
				}}
			>
				{__('Save.', 'wp-graphql-headless-login')}
			</Button>
		</>
	);
}
