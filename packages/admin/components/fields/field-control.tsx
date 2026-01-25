import { FormTokenFieldControl } from '@/admin/components/ui/form-token-field';
import {
	SelectControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { JwtSecretControl } from './jwt-secret-control';
import type { FieldSchema } from '@/admin/types';
import type { ComponentProps, ComponentType } from 'react';

type FieldControlProps = FieldSchema & {
	value: unknown;
	onChange: ( selected: unknown ) => void;
	disabled?: boolean;
};

// Define the prop types for each control component
type TextControlProps = ComponentProps< typeof TextControl >;
type ToggleControlProps = ComponentProps< typeof ToggleControl >;
type SelectControlProps = ComponentProps< typeof SelectControl >;
type FormTokenFieldControlProps = ComponentProps<
	typeof FormTokenFieldControl
>;
type JwtSecretControlProps = ComponentProps< typeof JwtSecretControl >;

type ControlComponentPropsMap = {
	text: TextControlProps;
	toggle: ToggleControlProps;
	select: SelectControlProps;
	formTokenField: FormTokenFieldControlProps;
	jwtSecret: JwtSecretControlProps;
};

type AllowedControlTypes = keyof ControlComponentPropsMap;

// Define the control component type
type ControlComponentType< T extends AllowedControlTypes > = ComponentType<
	ControlComponentPropsMap[ T ]
>;

/**
 * The map of control components.
 */
const CONTROL_COMPONENTS: {
	[ K in AllowedControlTypes ]: ControlComponentType< K >;
} = {
	text: TextControl,
	toggle: ToggleControl,
	select: SelectControl,
	formTokenField: FormTokenFieldControl,
	jwtSecret: JwtSecretControl,
};

/**
 * Get the control type for a given field type.
 */
const getControlType = ( type: string ): AllowedControlTypes => {
	switch ( type ) {
		case 'string':
		case 'integer':
			return 'text';
		case 'boolean':
			return 'toggle';
		case 'array':
			return 'formTokenField';
		default:
			console.warn( `Unknown field type: ${ type }` ); // eslint-disable-line no-console
			return 'text';
	}
};

export const FieldControl = ( {
	controlType: originalControlType,
	description,
	disabled,
	help,
	isAdvanced,
	label,
	onChange,
	required,
	type,
	value: originalValue,
	...rest
}: FieldControlProps ) => {
	const controlType =
		( originalControlType as AllowedControlTypes ) ||
		getControlType( type );

	const ControlComponent =
		( CONTROL_COMPONENTS?.[ controlType ] as ControlComponentType<
			typeof controlType
		> ) || undefined;

	if ( ! ControlComponent ) {
		return null;
	}

	// Fallback to the default value if the value is not set.
	const value = originalValue ?? rest?.default;

	// Build the component props based on control type.
	let componentProps: ControlComponentPropsMap[ typeof controlType ];

	switch ( controlType ) {
		case 'text':
			if ( type === 'string' ) {
				componentProps = {
					label: label || description,
					required: required || false,
					help: help || undefined,
					disabled: disabled || false,
					value: ( value as string ) || '',
					onChange,
					...( rest?.controlOverrides || {} ),
				} as TextControlProps;
			} else if ( type === 'integer' ) {
				componentProps = {
					label: label || description,
					required: required || false,
					help: help || undefined,
					disabled: disabled || false,
					value: value ? parseInt( value as string ) : '',
					onChange: ( selected: unknown ) =>
						onChange( parseInt( selected as string ) ),
					type: 'number',
					...( rest?.controlOverrides || {} ),
				} as TextControlProps;
			} else {
				componentProps = {
					label: label || description,
					required: required || false,
					help: help || undefined,
					disabled: disabled || false,
					value: ( value as string ) || '',
					onChange,
					...( rest?.controlOverrides || {} ),
				} as TextControlProps;
			}
			break;
		case 'toggle':
			componentProps = {
				label: label || description,
				required: required || false,
				help: help || undefined,
				disabled: disabled || false,
				checked: !! value || false,
				onChange: ( selected: boolean ) => onChange( !! selected ),
				...( rest?.controlOverrides || {} ),
			} as ToggleControlProps;
			break;
		case 'select':
			componentProps = {
				label: label || description,
				required: required || false,
				help: help || undefined,
				disabled: disabled || false,
				value: ( value as string ) || '',
				onChange,
				options:
					rest?.enum?.map( ( v: string ) => ( {
						label: v.charAt( 0 ).toUpperCase() + v.slice( 1 ),
						value: v,
					} ) ) || [],
				...( rest?.controlOverrides || {} ),
			} as SelectControlProps;
			break;
		case 'formTokenField':
			componentProps = {
				label: label || description,
				required: required || false,
				help: help || undefined,
				disabled: disabled || false,
				onChange,
				tokenizeOnSpace: true,
				value: Array.isArray( value ) ? value : [],
				...( rest?.controlOverrides || {} ),
			} as FormTokenFieldControlProps;
			break;
		case 'jwtSecret':
			componentProps = {
				help: help || '',
				label: label || '',
				...( rest?.controlOverrides || {} ),
			} as JwtSecretControlProps;
			break;
		default:
			componentProps = {
				label: label || description,
				required: required || false,
				help: help || undefined,
				disabled: disabled || false,
				value: ( value as string ) || '',
				onChange,
				...( rest?.controlOverrides || {} ),
			} as TextControlProps;
	}

	return <ControlComponent { ...componentProps } />;
};
