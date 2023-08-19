import { Option } from './Option';
import type { SettingSchema } from '../../types';

export function OptionList( {
	excludedProperties,
	options,
	optionsSchema,
	setOption,
}: {
	excludedProperties?: string[];
	options: Record< string, unknown >;
	optionsSchema: SettingSchema;
	setOption: ( options: Record< string, unknown > ) => void;
} ): JSX.Element {
	const excluded = excludedProperties || [ 'id', 'order' ];

	// Sort ascending client option schema by order property key.
	const sortedOptionsSchema = Object.keys( optionsSchema )?.sort(
		( a, b ) => {
			const aOrder = optionsSchema[ a ]?.order || 0;
			const bOrder = optionsSchema[ b ]?.order || 0;
			return aOrder > bOrder ? 1 : -1;
		}
	);

	return (
		<>
			{ sortedOptionsSchema?.map( ( option ) => {
				if ( excluded.includes( option ) ) {
					return null;
				}

				return (
					<Option
						key={ option }
						schema={ optionsSchema[ option ] }
						currentValue={ options?.[ option ] }
						setValue={ ( value ) => {
							setOption( {
								[ option ]: value,
							} );
						} }
					/>
				);
			} ) }
		</>
	);
}
