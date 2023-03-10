/**
 * External dependencies.
 */
import { Icon, PanelBody, PanelRow } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import type { wpGraphQLLogin } from '../..';
import { JwtSecretControl } from './JwtSecretControl';
import { PluginOptionList } from './PluginOptionList';

const CustomOptions = () => {
	return wpGraphQLLogin.hooks.applyFilters(
		'graphql_login_custom_plugin_options',
		<></>
	);
};

export function PluginSettings({ showAdvancedSettings }) {
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
				<PluginOptionList
					optionKey={option}
					key={option}
					showAdvancedSettings={showAdvancedSettings}
				/>
			))}

			<CustomOptions />
		</PanelBody>
	);
}
