import { FieldControl } from './field-control';
import { PanelRow } from '@wordpress/components';
import { useSettings } from '@/admin/contexts/settings-context';
import type { PropsWithChildren } from 'react';
import type { FieldSchema } from '@/admin/types';

const FieldWrapper = ( {
	isAdvanced,
	children,
}: PropsWithChildren< { isAdvanced: boolean } > ) => {
	const { showAdvancedSettings } = useSettings();

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
		<FieldWrapper isAdvanced={ !! field.isAdvanced }>
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
