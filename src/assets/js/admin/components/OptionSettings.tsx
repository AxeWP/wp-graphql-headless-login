/**
 * External dependencies.
 */
import { PanelRow } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';

/**
 * Internal dependencies.
 */
import { OptionControl } from './OptionControl';

export function OptionSettings({ clientSlug, optionsKey, options, setOption }) {
	const [showAdvancedSettings] = useEntityProp(
		'root',
		'site',
		'wpgraphql_login_settings_show_advanced_settings'
	);

	const excludedProperties = ['id', 'order'];

	const optionsSchema =
		wpGraphQLLogin?.settings[clientSlug]?.properties?.[optionsKey]
			?.properties || {};

	// Sort ascending client option schema by order property key.
	const sortedOptionsSchema = Object.keys(optionsSchema)?.sort((a, b) => {
		const aOrder = optionsSchema[a].order;
		const bOrder = optionsSchema[b].order;
		return aOrder > bOrder ? 1 : -1;
	});

	return (
		<>
			{sortedOptionsSchema?.map((option) => {
				if (excludedProperties.includes(option)) {
					return null;
				}

				if (!showAdvancedSettings && optionsSchema[option]?.advanced) {
					return null;
				}

				return (
					<PanelRow key={option}>
						<OptionControl
							{...optionsSchema[option]}
							value={options?.[option]}
							onChange={(value) => {
								setOption({
									[option]: value,
								});
							}}
						/>
					</PanelRow>
				);
			})}
		</>
	);
}
