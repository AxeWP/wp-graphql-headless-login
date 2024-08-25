import { PanelRow } from '@wordpress/components';
import { OptionControl } from './OptionControl';
import { useAppContext } from '../../contexts/AppProvider';
import type { SettingSchema } from '../../types';

export function Option( {
	schema,
	currentValue,
	setValue,
}: {
	schema: SettingSchema;
	currentValue: unknown;
	setValue: ( value: unknown ) => void;
} ) {
	const { showAdvancedSettings } = useAppContext();

	if ( !! schema?.hidden ) {
		return null;
	}

	if ( ! showAdvancedSettings && schema?.advanced ) {
		return null;
	}

	return (
		<PanelRow>
			<OptionControl
				{ ...schema }
				value={ currentValue }
				onChange={ setValue }
			/>
		</PanelRow>
	);
}
