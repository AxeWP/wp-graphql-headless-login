import { Option } from './Option';

export function OptionList({
	excludedProperties,
	options,
	optionsSchema,
	setOption,
	showAdvancedSettings,
}: {
	excludedProperties?: string[];
	options: Record<string, any>;
	optionsSchema: Record<string, any>;
	setOption: (options: Record<string, any>) => void;
	showAdvancedSettings?: boolean;
}): JSX.Element {
	const excluded = excludedProperties || ['id', 'order'];

	// Sort ascending client option schema by order property key.
	const sortedOptionsSchema = Object.keys(optionsSchema)?.sort((a, b) => {
		const aOrder = optionsSchema[a]?.order;
		const bOrder = optionsSchema[b]?.order;
		return aOrder > bOrder ? 1 : -1;
	});

	return (
		<>
			{sortedOptionsSchema?.map((option) => {
				if (excluded.includes(option)) {
					return null;
				}

				return (
					<Option
						key={option}
						optionKey={option}
						schema={optionsSchema[option]}
						currentValue={options?.[option]}
						setValue={setOption}
						showAdvancedSettings={showAdvancedSettings}
					/>
				);
			})}
		</>
	);
}
