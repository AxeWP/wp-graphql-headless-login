import { useEntityProp } from '@wordpress/core-data';
import { Option } from '../../components';

export function PluginOptionList( { optionKey }: { optionKey: string } ) {
	const [ value, setValue ] = useEntityProp( 'root', 'site', optionKey );
	const schema = wpGraphQLLogin?.settings?.plugin?.[ optionKey ] || {};

	return (
		<Option
			key={ optionKey }
			schema={ schema }
			currentValue={ value }
			setValue={ ( v ) => setValue( v ) }
		/>
	);
}
