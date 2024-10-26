import type { FieldSchema } from '@/admin/types';
import { Field } from './field';

export const Fields = ( {
	excludedProperties,
	fields,
	values,
	setValue,
}: {
	excludedProperties?: string[];
	values: Record< string, unknown > | undefined;
	fields: Record< string, FieldSchema >;
	setValue: ( values: Record< string, unknown > ) => void;
} ) => {
	if ( ! values ) {
		return null;
	}

	const excluded = excludedProperties || [ 'id', 'order' ];

	// Sort ascending client field schema by order property key.
	const sortedFieldKeys = Object.keys( fields )?.sort( ( a, b ) => {
		const aOrder = fields[ a ]?.order || 0;
		const bOrder = fields[ b ]?.order || 0;
		return aOrder > bOrder ? 1 : -1;
	} );

	return (
		<>
			{ sortedFieldKeys?.map( ( fieldKey ) => {
				if ( excluded.includes( fieldKey ) ) {
					return null;
				}

				if ( fields[ fieldKey ]?.hidden ) {
					return null;
				}

				return (
					<Field
						key={ fieldKey }
						field={ fields[ fieldKey ] }
						value={ values[ fieldKey ] }
						setValue={ ( newValue ) => {
							setValue( {
								...values,
								[ fieldKey ]: newValue,
							} );
						} }
					/>
				);
			} ) }
		</>
	);
};
