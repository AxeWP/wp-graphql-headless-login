import { PanelBody } from '@wordpress/components';
import { JwtSecretControl } from './JwtSecretControl';
import { PluginOptionList } from './PluginOptionList';
import { useAppContext } from '../../contexts/AppProvider';

const CustomOptions = (): JSX.Element => {
	return wpGraphQLLogin.hooks.applyFilters(
		'graphql_login_custom_plugin_options',
		<></>
	) as JSX.Element;
};

function PluginSettings() {
	const { showAdvancedSettings } = useAppContext();

	const optionsSchema = wpGraphQLLogin?.settings?.plugin || {};

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
			{ showAdvancedSettings && <JwtSecretControl /> }

			{ sortedOptionsSchema.map( ( option ) => (
				<PluginOptionList optionKey={ option } key={ option } />
			) ) }

			<CustomOptions />
		</PanelBody>
	);
}

export default PluginSettings;
