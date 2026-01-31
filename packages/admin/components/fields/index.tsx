import type { FieldSchema } from '@/admin/types';
import { Field } from './field';

export const Fields = ( {
	excludedProperties,
	fields,
	values,
	setValue,
	validateConditionalLogic,
}: {
	excludedProperties?: string[];
	values: Record< string, unknown > | undefined;
	fields: Record< string, FieldSchema >;
	setValue: ( values: Record< string, unknown > ) => void;
	validateConditionalLogic?: ( field: string ) => boolean;
} ) => {
	if ( ! values ) {
		return null;
	}

	const excluded = excludedProperties || [ 'id', 'order' ];

	// Sort ascending client field schema by order property key.
	const sortedFieldKeys = Object.keys( fields )?.sort( ( a, b ) => {
		const aOrder = fields[ a ]?.order ?? 0;
		const bOrder = fields[ b ]?.order ?? 0;
		return aOrder - bOrder;
	} );

	const fieldsToRender = sortedFieldKeys
		?.filter( ( fieldKey ) => {
			if ( excluded.includes( fieldKey ) ) {
				return false;
			}

			if ( ! fields?.[ fieldKey ] || fields[ fieldKey ]?.hidden ) {
				return false;
			}

			return true;
		} )
		.map( ( fieldKey ) => {
			const isConditionMet = validateConditionalLogic
				? validateConditionalLogic( fieldKey )
				: true;

			return {
				key: fieldKey,
				isConditionMet,
			};
		} );

	return (
		<>
			{ fieldsToRender?.map( ( { key: fieldKey, isConditionMet } ) => (
				<Field
					key={ fieldKey }
					field={ fields[ fieldKey ]! }
					value={ values[ fieldKey ] }
					isConditionMet={ isConditionMet }
					setValue={ ( newValue ) => {
						setValue( {
							...values,
							[ fieldKey ]: newValue,
						} );
					} }
				/>
			) ) }
		</>
	);
};
