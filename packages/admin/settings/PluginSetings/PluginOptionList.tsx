/**
 * External dependencies.
 */
import { useEntityProp } from '@wordpress/core-data';

/**
 * Internal dependencies.
 */
import { Option } from '../../components';

export function PluginOptionList({ optionKey }) {
	const [value, setValue] = useEntityProp('root', 'site', optionKey);
	const schema = wpGraphQLLogin?.settings?.plugin?.[optionKey] || {};

	return (
		<Option
			key={optionKey}
			optionKey={optionKey}
			schema={schema}
			currentValue={value}
			onChange={setValue}
		/>
	);
}
