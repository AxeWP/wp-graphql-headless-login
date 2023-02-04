/**
 * External dependencies.
 */
import {
	Icon,
	PanelBody,
	PanelRow,
	ToggleControl,
} from '@wordpress/components';
import { store as coreStore, useEntityProp } from '@wordpress/core-data';
import { useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { ClientSecretControl } from './ClientSecretControl';

/**
 * Internal dependencies.
 */

export function PluginOptions() {
	const [deleteDataOnDeactivate, setDeleteDataOnDeactivate] = useEntityProp(
		'root',
		'site',
		'wpgraphql_login_settings_delete_data_on_deactivate'
	);

	const [enablePasswordMutation, setEnablePasswordMutation] = useEntityProp(
		'root',
		'site',
		'wpgraphql_login_settings_enable_password_mutation'
	);

	const [usePasswordAuthCookie, setUsePasswordAuthCookie] = useEntityProp(
		'root',
		'site',
		'wpgraphql_login_settings_password_use_auth_cookie'
	);

	const { saveEditedEntityRecord } = useDispatch(coreStore);

	const CustomOptions = () => {
		return wpGraphQLLogin.hooks.applyFilters(
			'graphql_login_custom_plugin_options',
			<></>
		);
	};

	// Set default value for enablePasswordMutation.
	if (null === enablePasswordMutation) {
		setEnablePasswordMutation(true);
		saveEditedEntityRecord('root', 'site', undefined, {
			wpgraphql_login_settings_enable_password_mutation: true,
		});
	}

	return (
		<PanelBody>
			<PanelRow>
				<h2 className="components-panel__body-title">
					{__('Plugin Settings', 'wp-graphql-headless-login')}
					<Icon
						icon="admin-tools"
						className="components-panel__icon"
						size={20}
					/>
				</h2>
			</PanelRow>

			<ClientSecretControl />

			{enablePasswordMutation !== undefined && (
				<ToggleControl
					className="wp-graphql-headless-login__enable-password-mutation-toggle"
					label={__(
						'Enable password mutation',
						'wp-graphql-headless-login'
					)}
					checked={enablePasswordMutation}
					onChange={(value) => {
						setEnablePasswordMutation(value);
						saveEditedEntityRecord('root', 'site', undefined, {
							wpgraphql_login_settings_enable_password_mutation:
								value,
						});
					}}
					help={__(
						'When selected, the `loginWithPassword` mutation will be enabled. This allows users to login with their existing WordPress site credentials.',
						'wp-graphql-headless-login'
					)}
				/>
			)}

			{!!enablePasswordMutation &&
				usePasswordAuthCookie !== undefined && (
					<ToggleControl
						className="wp-graphql-headless-login__use-password-auth-cookie-toggle"
						label={__(
							'Set authentication cookie on password login',
							'wp-graphql-headless-login'
						)}
						checked={usePasswordAuthCookie}
						onChange={(value) => {
							setUsePasswordAuthCookie(value);
							saveEditedEntityRecord('root', 'site', undefined, {
								wpgraphql_login_settings_password_use_auth_cookie:
									value,
							});
						}}
						help={__(
							'When selected, the `loginWithPassword` mutation will set the authentication cookie on successful login. This is useful for granting access to the WordPress dashboard or other protected areas of the WordPress backend without having to re-authenticate.',
							'wp-graphql-headless-login'
						)}
					/>
				)}

			{deleteDataOnDeactivate !== undefined && (
				<ToggleControl
					className="wp-graphql-headless-login__delete-on-deactivate-toggle"
					label={__(
						'Delete plugin data on deactivate',
						'wp-graphql-headless-login'
					)}
					checked={deleteDataOnDeactivate}
					onChange={(value) => {
						setDeleteDataOnDeactivate(value);
						saveEditedEntityRecord('root', 'site', undefined, {
							wpgraphql_login_settings_delete_data_on_deactivate:
								value,
						});
					}}
					help={__(
						'When selected, all plugin data will be deleted when the plugin is deactivated, including client configurations. Mote: No user meta will be deleted.',
						'wp-graphql-headless-login'
					)}
				/>
			)}

			<CustomOptions />
		</PanelBody>
	);
}
