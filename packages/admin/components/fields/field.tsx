import { FieldControl } from './field-control';
import { PanelRow } from '@wordpress/components';
import { useSettings } from '@/admin/contexts/settings-context';
import type { FieldSchema } from '@/admin/types';

export const Field = ( {
	field,
	value,
	setValue,
	isConditionMet = true,
}: {
	field?: FieldSchema;
	value: unknown;
	setValue: ( value: unknown ) => void;
	isConditionMet?: boolean;
} ) => {
	const { showAdvancedSettings } = useSettings();

	if ( ! field || ! isConditionMet ) {
		return null;
	}

	if ( ! showAdvancedSettings && !! field.advanced ) {
		return null;
	}

	return (
		<PanelRow>
			<FieldControl
				{ ...field }
				value={ value }
				onChange={ ( newValue ) => {
					setValue( newValue );
				} }
				disabled={ ! isConditionMet }
			/>
		</PanelRow>
	);
};
