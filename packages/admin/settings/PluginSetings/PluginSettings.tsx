import { Icon, PanelBody, PanelRow } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { JwtSecretControl } from './JwtSecretControl';
import { PluginOptionList } from './PluginOptionList';
import { useAppContext } from '../../contexts/AppProvider';
import type { PluginSettingsType, SettingSchema } from '../../types';

const CustomOptions = (): JSX.Element => {
	return wpGraphQLLogin.hooks.applyFilters(
		'graphql_login_custom_plugin_options',
		<></>
	) as JSX.Element;
};

export function PluginSettings() {
	const { showAdvancedSettings } = useAppContext();

	const optionsSchema =
		wpGraphQLLogin?.settings?.plugin ||
		( {} satisfies Record< keyof PluginSettingsType, SettingSchema > );

	// Sort ascending client option schema by order property key.
	const sortedOptionsSchema = Object.keys( optionsSchema )
		?.sort( ( a, b ) => {
			const aOrder = optionsSchema[ a ]?.order || 0;
			const bOrder = optionsSchema[ b ]?.order || 0;
			return aOrder > bOrder ? 1 : -1;
		} )
		.filter( ( option ) => ! optionsSchema[ option ]?.hidden );

	return (
		<PanelBody>
			<PanelRow>
				<h2 className="components-panel__body-title">
					{ __( 'Plugin Settings', 'wp-graphql-headless-login' ) }
					<Icon
						icon="admin-tools"
						className="components-panel__icon"
						size={ 20 }
					/>
				</h2>
			</PanelRow>

			{ showAdvancedSettings && <JwtSecretControl /> }

			{ sortedOptionsSchema.map( ( option ) => (
				<PluginOptionList optionKey={ option } key={ option } />
			) ) }

			<CustomOptions />
		</PanelBody>
	);
}
