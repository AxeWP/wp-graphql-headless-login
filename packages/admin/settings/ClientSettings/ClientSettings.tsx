/**
 * External Dependencies
 */
import { useEffect, useCallback } from '@wordpress/element';
import { Button, Icon, PanelBody, PanelRow } from '@wordpress/components';
import { sprintf, __ } from '@wordpress/i18n';
import { store as coreStore, useEntityProp } from '@wordpress/core-data';
import { useDispatch, dispatch, useSelect } from '@wordpress/data';
import { ClientOptionList } from './ClientOptionList';
import { OptionList } from '../../components';
import type { AccessControlEntityProps } from '../AccessControlSettings';

const clientDefaults: ClientType = {
	name: 'New client',
	order: 0,
	isEnabled: false,
	clientOptions: {},
	loginOptions: {
		useAuthenticationCookie: false,
	},
};

export type ClientType = {
	id: string;
	name: string;
	order: number;
	isEnabled: boolean;
	clientOptions: object;
	loginOptions: LoginOptionSetttingsType;
};

export function ClientSettings({ clientSlug }) {
	const { saveEditedEntityRecord } = useDispatch(coreStore);
	const [client, setClient] = useEntityProp('root', 'site', clientSlug);
	const [accessControlSettings]: AccessControlEntityProps = useEntityProp(
		'root',
		'site',
		'wpgraphql_login_access_control'
	);

	const { lastError } = useSelect(
		(select) => ({
			lastError: select(coreStore).getLastEntitySaveError('root', 'site'),
			isSaving: select(coreStore).isSavingEntityRecord('root', 'site'),
			hasEdits: select(coreStore).hasEditsForEntityRecord('root', 'site'),
		}),
		[client]
	);

	const updateClient = useCallback(
		(key: string, value: unknown) => {
			const newClient = {
				...client,
				[key]: value,
			};
			setClient(newClient);
		},
		[client, setClient]
	);

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

	useEffect(() => {
		if (undefined !== client && Object.keys(client || {})?.length === 0) {
			setClient({
				...clientDefaults,
				slug: clientSlug.replace('wpgraphql_login_provider_', ''),
			});
		}
	}, [client, setClient, clientSlug]);

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

	// Disable siteToken if shouldBlockUnauthorizedDomains is false
	useEffect(() => {
		if (
			!accessControlSettings?.shouldBlockUnauthorizedDomains &&
			clientSlug === 'wpgraphql_login_provider_siteToken' &&
			client?.isEnabled
		) {
			updateClient('isEnabled', false);

			dispatch('core/notices').createErrorNotice(
				__(
					'The Site Token provider can only be enabled if `Access Control Settings: Block unauthorized domains` is enabled.',
					'wp-graphql-headless-login'
				),
				{
					type: 'snackbar',
					isDismissible: true,
					explicitDismiss: true,
				}
			);
		}
	}, [accessControlSettings, clientSlug, client, updateClient]);

	const saveRecord = async () => {
		const saved = await saveEditedEntityRecord('root', 'site', undefined, {
			[clientSlug]: client,
		});

		if (saved) {
			dispatch('core/notices').createNotice('success', 'Settings saved', {
				type: 'snackbar',
				isDismissible: true,
				explicitDismiss: true,
			});
		}
	};

	const CustomPanel = (): JSX.Element => {
		return wpGraphQLLogin.hooks.applyFilters(
			'graphql_login_custom_client_settings',
			<></>,
			clientSlug,
			client
		) as JSX.Element;
	};

	return (
		<>
			<PanelBody>
				<PanelRow>
					<h2 className="components-panel__body-title">
						{sprintf(
							// translators: %s: Client slug.
							__('%s Settings', 'wp-graphql-headless-login'),
							wpGraphQLLogin?.settings?.providers?.[clientSlug]
								?.name?.default || 'Provider'
						)}
					</h2>
				</PanelRow>
				<OptionList
					excludedProperties={[
						'loginOptions',
						'clientOptions',
						'order',
					]}
					options={client}
					optionsSchema={
						wpGraphQLLogin?.settings?.providers?.[clientSlug]
					}
					setOption={(value) => {
						setClient({
							...client,
							...value,
						});
					}}
				/>
				<ClientOptionList
					clientSlug={clientSlug}
					optionsKey="clientOptions"
					options={client?.clientOptions}
					setOption={setClientOption}
				/>
			</PanelBody>
			<PanelBody>
				<PanelRow>
					<h2 className="components-panel__body-title">
						{__('Login Settings', 'wp-graphql-headless-login')}
						<Icon
							icon="admin-users"
							className="components-panel__icon"
							size={20}
						/>
					</h2>
				</PanelRow>

				<ClientOptionList
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
				{__('Save Provider', 'wp-graphql-headless-login')}
			</Button>
		</>
	);
}
