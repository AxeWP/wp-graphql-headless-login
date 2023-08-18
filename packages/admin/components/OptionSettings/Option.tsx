import { PanelRow } from '@wordpress/components';
import { OptionControl } from './OptionControl';

export function Option({
	optionKey,
	schema,
	currentValue,
	setValue,
	showAdvancedSettings,
}: {
	optionKey: string;
	schema: Record<string, any>;
	currentValue: any;
	setValue: (options: Record<string, any>) => void;
	showAdvancedSettings?: boolean;
}) {
	if (!!schema?.hidden) {
		return null;
	}

	if (!showAdvancedSettings && schema?.advanced) {
		return null;
	}

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
