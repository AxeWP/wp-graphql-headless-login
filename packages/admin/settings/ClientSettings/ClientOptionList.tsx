import { Fields } from '@/admin/components/fields';
import type { ClientOptionsType, LoginOptionsType } from '../../types';

export function ClientOptionList( {
	clientSlug,
	optionsKey,
	options,
	setOption,
}: {
	clientSlug: string;
	optionsKey: string;
	options: ClientOptionsType | LoginOptionsType;
	setOption: ( value: ClientOptionsType | LoginOptionsType ) => void;
} ) {
	const excludedProperties = [ 'id', 'order' ];

	const optionsSchema =
		wpGraphQLLogin?.settings?.providers?.[ clientSlug ]?.[ optionsKey ]
			?.properties || {};

	return (
		<Fields
			fields={ optionsSchema }
			values={ options }
			setValue={ setOption }
			excludedProperties={ excludedProperties }
		/>
	);
}
