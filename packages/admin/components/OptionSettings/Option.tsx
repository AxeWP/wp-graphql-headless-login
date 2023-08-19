import { PanelRow } from '@wordpress/components';
import { OptionControl } from './OptionControl';
import { useAppContext } from '../../contexts/AppProvider';

export function Option( {
	schema,
	currentValue,
	setValue,
}: {
	schema: Record< string, any >;
	currentValue: any;
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
