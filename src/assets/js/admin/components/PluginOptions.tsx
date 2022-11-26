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

	const { saveEditedEntityRecord } = useDispatch(coreStore);

	const CustomOptions = () => {
		return wpGraphQLLogin.hooks.applyFilters(
			'graphql_login_custom_plugin_options',
			<></>
		);
	};

	return (
		<PanelBody>
			<PanelRow>
				<h2 className="components-panel__body-title">
					{__('Plugin Settings.', 'wp-graphql-headless-login')}
					<Icon
						icon="admin-tools"
						className="components-panel__icon"
						size={20}
					/>
				</h2>
			</PanelRow>

			<ClientSecretControl />

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
