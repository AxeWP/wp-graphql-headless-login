import { Field } from '@/admin/components/fields/field';
import { useEntityProp } from '@wordpress/core-data';
import { useEffect } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

export function PluginOptionList( { optionKey }: { optionKey: string } ) {
	const [ value, setValue, error ] = useEntityProp(
		'root',
		'site',
		optionKey
	);
	const schema = wpGraphQLLogin?.settings?.plugin?.[ optionKey ] || {};

	useEffect( () => {
		if ( error ) {
			// @ts-expect-error this isnt typed.
			dispatch( 'core/notices' ).createErrorNotice(
				sprintf(
					// translators: %s: Error message.
					__(
						'Error saving settings: %s',
						'wp-graphql-headless-login'
					),
					error?.message
				),
				{
					type: 'snackbar',
					isDismissible: true,
				}
			);
		}
	}, [ error ] );

	return (
		<Field
			key={ optionKey }
			field={ schema }
			value={ value }
			setValue={ ( v ) => setValue( v ) }
		/>
	);
}
