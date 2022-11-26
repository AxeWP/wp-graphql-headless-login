/**
 * External Dependencies
 */
import { Fragment } from '@wordpress/element';
import {
	FormTokenField,
	TextControl,
	ToggleControl,
	SelectControl,
} from '@wordpress/components';

const controls = {
	string: TextControl,
	select: SelectControl,
	boolean: ToggleControl,
	array: FormTokenField,
};

export function OptionControl({
	type,
	description,
	value,
	required,
	onChange,
	help,
	...rest
}) {
	const componentProps = {
		label: description,
		required: required ?? false,
		help: help || null,
	};
	let control = Fragment;

	switch (type) {
		case 'string':
			control = rest?.enum?.length ? controls.select : controls.string;

			componentProps.value = value || '';
			componentProps.onChange = (selected) => onChange(selected);

			if (rest?.enum?.length) {
				componentProps.options = rest?.enum.map((v) => ({
					label: v.charAt(0).toUpperCase() + v.slice(1),
					value: v,
				}));
			}
			break;
		case 'integer':
			control = controls.string;

			componentProps.value = value ? parseInt(value) : '';
			componentProps.onChange = (selected) =>
				onChange(parseInt(selected));
			componentProps.type = 'number';
			break;
		case 'boolean':
			control = controls.boolean;

			componentProps.checked = value || false;
			componentProps.onChange = (selected) => onChange(selected);
			break;
		case 'array':
			control = controls.array;

			componentProps.onChange = (selected) => onChange(selected);
			componentProps.tokenizeOnSpace = true;
			componentProps.value = value || [];
			break;
	}

	const Component = control;

	return <Component {...componentProps} />;
}
