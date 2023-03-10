/**
 * External dependencies.
 */
import { PanelRow } from '@wordpress/components';

/**
 * Internal dependencies.
 */
import { OptionControl } from './OptionControl';

interface OptionProps {
	optionKey: string;
	currentValue: any;
	schemachema: Record<string, any>;
	setOption: (options: Record<string, any>) => void;
	showAdvancedSettings?: boolean;
}

export function Option({
	optionKey,
	schema,
	currentValue,
	setValue,
	showAdvancedSettings,
}: OptionProps): JSX.Element {
	if (!!schema?.hidden) {
		return null;
	}

	if (!showAdvancedSettings && schema?.advanced) {
		return null;
	}

	// console.warn({ option, optionsSchema: optionsSchema[option] });

	return (
		<PanelRow>
			<OptionControl
				{...schema}
				value={currentValue}
				onChange={(value) => {
					setValue({
						[optionKey]: value,
					});
				}}
			/>
		</PanelRow>
	);
}
