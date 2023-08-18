import {
	FormTokenField,
	TextControl,
	ToggleControl,
	SelectControl,
	BaseControl,
	useBaseControlProps,
} from '@wordpress/components';

const FormTokenControl = ({
	help,
	...props
}: typeof FormTokenField & {
	help?: string;
}) => {
	const { baseControlProps, controlProps } = useBaseControlProps(props);

	return (
		<BaseControl help={help || null} {...baseControlProps}>
			<FormTokenField {...controlProps} {...props} />
		</BaseControl>
	);
};

const controls = {
	string: TextControl,
	select: SelectControl,
	boolean: ToggleControl,
	array: FormTokenControl,
};

export function OptionControl({
	type,
	description,
	value,
	required,
	label,
	onChange,
	help,
	...rest
}) {
	const componentProps = {
		label: label || description,
		required: required ?? false,
		help: help || null,
	};
	let control;

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
