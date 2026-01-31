import { FieldControl } from './field-control';
import { PanelRow } from '@wordpress/components';
import { useSettings } from '@/admin/contexts/settings-context';
import type { PropsWithChildren } from 'react';
import type { FieldSchema } from '@/admin/types';

const FieldWrapper = ( {
	isAdvanced,
	isConditionMet,
	children,
}: PropsWithChildren< { isAdvanced: boolean; isConditionMet: boolean } > ) => {
	const { showAdvancedSettings } = useSettings();

	if ( ! showAdvancedSettings && isAdvanced ) {
		return null;
	}

	return (
		<PanelRow data-condition-met={ isConditionMet.toString() }>
			{ children }
		</PanelRow>
	);
};

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
	if ( ! field ) {
		return null;
	}

	return (
		<FieldWrapper
			isAdvanced={ !! field.isAdvanced }
			isConditionMet={ isConditionMet }
		>
			<FieldControl
				{ ...field }
				value={ value }
				onChange={ ( newValue ) => {
					setValue( newValue );
				} }
				disabled={ ! isConditionMet }
			/>
		</FieldWrapper>
	);
};
