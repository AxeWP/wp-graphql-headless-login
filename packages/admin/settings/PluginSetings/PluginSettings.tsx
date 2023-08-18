import { Icon, PanelBody, PanelRow } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { JwtSecretControl } from './JwtSecretControl';
import { PluginOptionList } from './PluginOptionList';
import { useAppContext } from '../../contexts/AppProvider';

const CustomOptions = (): JSX.Element => {
	return wpGraphQLLogin.hooks.applyFilters(
		'graphql_login_custom_plugin_options',
		<></>
	) as JSX.Element;
};

export function PluginSettings() {
	const { showAdvancedSettings } = useAppContext();

	const optionsSchema = wpGraphQLLogin?.settings?.plugin || {};

	// Sort ascending client option schema by order property key.
	const sortedOptionsSchema = Object.keys(optionsSchema)?.sort((a, b) => {
		const aOrder = optionsSchema[a]?.order;
		const bOrder = optionsSchema[b]?.order;
		return aOrder > bOrder ? 1 : -1;
	});

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

			{showAdvancedSettings && <JwtSecretControl />}

			{sortedOptionsSchema.map((option) => (
				<PluginOptionList optionKey={option} key={option} />
			))}

			<CustomOptions />
		</PanelBody>
	);
}
