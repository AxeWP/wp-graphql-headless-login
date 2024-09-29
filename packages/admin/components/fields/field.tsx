import { FieldControl } from './field-control';
import { PanelRow } from '@wordpress/components';
import { useAppContext } from '@/admin/contexts/AppProvider';
import type { PropsWithChildren } from 'react';
import type { FieldSchema } from '@/admin/types';

const FieldWrapper = ( {
	isAdvanced,
	children,
}: PropsWithChildren< { isAdvanced: boolean } > ) => {
	const { showAdvancedSettings } = useAppContext();

	if ( ! showAdvancedSettings && isAdvanced ) {
		return null;
	}

	return <PanelRow>{ children }</PanelRow>;
};

export const Field = ( {
	field,
	value,
	setValue,
}: {
	field: FieldSchema;
	value: unknown;
	setValue: ( value: unknown ) => void;
} ) => {
	return (
		<FieldWrapper isAdvanced={ !! field.advanced }>
			<FieldControl
				{ ...field }
				value={ value }
				onChange={ ( newValue ) => {
					setValue( newValue );
				} }
			/>
		</FieldWrapper>
	);
};
