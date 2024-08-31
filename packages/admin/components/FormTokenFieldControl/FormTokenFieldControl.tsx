import { FormTokenField } from '@wordpress/components';
import type { FormTokenFieldProps } from '@wordpress/components/build-types/form-token-field/types';
import { useInstanceId } from '@wordpress/compose';

export type FormTokenFieldControlProps = FormTokenFieldProps & {
	help?: string;
};

/**
 * Wraps the FormTokenField component from WordPress components library, to add a help prop.
 */
export function FormTokenFieldControl( {
	help,
	...props
}: FormTokenFieldControlProps ) {
	// The `help` prop is a <p> element added below the FormTokenField component.

	const instanceId = useInstanceId( FormTokenField );

	return (
		<fieldset className="components-form-token-field-control">
			<FormTokenField { ...props } />
			{ help && (
				<p
					id={ `components-form-token-additional-help-${ instanceId }` }
					className="help components-form-token-field__help"
					style={ {
						// Mimics base-control/styles/base-control-styles
						marginBlockStart: '0.5em',
						fontSize: '12px',
						fontStyle: 'italic',
					} }
				>
					{ help }
				</p>
			) }
		</fieldset>
	);
}
