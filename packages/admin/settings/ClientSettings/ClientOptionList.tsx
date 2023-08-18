/**
 * Internal dependencies.
 */
import { OptionList } from '../../components';

export function ClientOptionList({
	clientSlug,
	optionsKey,
	options,
	setOption,
}) {
	const excludedProperties = ['id', 'order'];

	const optionsSchema =
		wpGraphQLLogin?.settings?.providers?.[clientSlug]?.[optionsKey]
			?.properties || {};

	return (
		<OptionList
			optionsSchema={optionsSchema}
			options={options}
			setOption={setOption}
			excludedProperties={excludedProperties}
		/>
	);
}
