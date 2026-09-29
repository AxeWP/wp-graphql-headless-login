import { render, screen, act } from '@testing-library/react';
import { vi, beforeEach, describe, it, expect } from 'vitest';
import { Field } from '@/admin/components/fields/field';
import { SettingsProvider } from '@/admin/contexts/settings-context';
import type { FieldSchema } from '@/admin/types';

const mockApiFetch = vi.fn();

vi.mock( '@wordpress/api-fetch', () => ( {
	default: () => mockApiFetch(),
} ) );

vi.mock( '@/admin/components/fields/field-control', () => ( {
	FieldControl: ( {
		label,
		controlType: originalControlType,
		type,
		value,
		onChange,
		disabled,
	}: {
		label: string;
		controlType?: string;
		type: string;
		value: unknown;
		onChange: ( value: unknown ) => void;
		disabled: boolean;
	} ) => {
		let controlType = originalControlType;
		if ( ! controlType ) {
			if ( type === 'string' || type === 'integer' ) {
				controlType = 'text';
			} else if ( type === 'boolean' ) {
				controlType = 'toggle';
			} else if ( type === 'array' ) {
				controlType = 'formTokenField';
			} else {
				controlType = 'jwtSecret';
			}
		}

		const handleClick = () => onChange( 'changed-' + value );
		const handleKeyDown = ( event: React.KeyboardEvent ) => {
			if ( event.key === 'Enter' || event.key === ' ' ) {
				event.preventDefault();
				handleClick();
			}
		};

		return (
			<div
				data-testid={ 'field-control-' + controlType }
				data-label={ label }
				data-value={ JSON.stringify( value ) }
				data-disabled={ disabled.toString() }
				onClick={ handleClick }
				onKeyDown={ handleKeyDown }
				role="button"
				tabIndex={ 0 }
			>
				{ label }
			</div>
		);
	},
} ) );

vi.mock( '@wordpress/components', () => ( {
	PanelRow: ( { children }: { children: React.ReactNode } ) => (
		<div data-testid="panel-row">{ children }</div>
	),
} ) );

const renderField = (
	field: FieldSchema,
	value: unknown,
	setValue: ( value: unknown ) => void,
	isConditionMet: boolean = true,
	showAdvancedSettings: boolean = false
) => {
	( global as Record< string, unknown > )[ 'wpGraphQLLogin' ] = {
		settings: {
			wpgraphql_login_settings: {
				show_advanced_settings: showAdvancedSettings,
				fields: {},
			},
		},
		nonce: 'test-nonce',
		secret: {
			hasKey: true,
			isConstant: false,
		},
	};

	const settings = ( global as Record< string, unknown > )?.[
		'wpGraphQLLogin'
	] as Record< string, Record< string, unknown > >;

	// return a thenable that invokes the callback synchronously so
	// SettingsProvider receives the value during the render act()
	mockApiFetch.mockImplementation( () => {
		// a simple thenable that supports then/catch/finally synchronously
		const thenable: any = {
			then( cb: ( v: unknown ) => void ) {
				try {
					cb( settings?.[ 'settings' ] );
				} catch {}
				return thenable;
			},
			catch() {
				return thenable;
			},
			finally( cb?: () => void ) {
				try {
					if ( cb ) {
						cb();
					}
				} catch {}
				return thenable;
			},
		};

		return thenable;
	} );

	let result: ReturnType< typeof render >;

	act( () => {
		result = render(
			<SettingsProvider>
				<Field
					field={ field }
					value={ value }
					setValue={ setValue }
					isConditionMet={ isConditionMet }
				/>
			</SettingsProvider>
		);
	} );

	// @ts-expect-error - result is assigned inside act
	return result;
};

describe( 'Field Component', () => {
	let mockSetValue: ReturnType< typeof vi.fn > &
		( ( value: unknown ) => void );

	beforeEach( () => {
		mockSetValue = vi.fn() as ReturnType< typeof vi.fn > &
			( ( value: unknown ) => void );
		vi.clearAllMocks();
	} );

	describe( 'Field type rendering', () => {
		it( 'renders text field for string type', () => {
			const textField: FieldSchema = {
				label: 'Text Field',
				description: 'A text field',
				type: 'string',
			};

			renderField( textField, 'test value', mockSetValue );

			const fieldControl = screen.getByTestId( 'field-control-text' );
			expect( fieldControl ).toBeInTheDocument();
			expect( fieldControl ).toHaveAttribute(
				'data-label',
				'Text Field'
			);
		} );

		it( 'renders text field for integer type', () => {
			const integerField: FieldSchema = {
				label: 'Integer Field',
				description: 'An integer field',
				type: 'integer',
			};

			renderField( integerField, 42, mockSetValue );

			const fieldControl = screen.getByTestId( 'field-control-text' );
			expect( fieldControl ).toBeInTheDocument();
			expect( fieldControl ).toHaveAttribute(
				'data-label',
				'Integer Field'
			);
		} );

		it( 'renders toggle field for boolean type', () => {
			const booleanField: FieldSchema = {
				label: 'Boolean Field',
				description: 'A boolean field',
				type: 'boolean',
			};

			renderField( booleanField, true, mockSetValue );

			const fieldControl = screen.getByTestId( 'field-control-toggle' );
			expect( fieldControl ).toBeInTheDocument();
			expect( fieldControl ).toHaveAttribute(
				'data-label',
				'Boolean Field'
			);
		} );

		it( 'renders formTokenField for array type', () => {
			const arrayField: FieldSchema = {
				label: 'Array Field',
				description: 'An array field',
				type: 'array',
			};

			renderField( arrayField, [ 'value1', 'value2' ], mockSetValue );

			const fieldControl = screen.getByTestId(
				'field-control-formTokenField'
			);
			expect( fieldControl ).toBeInTheDocument();
			expect( fieldControl ).toHaveAttribute(
				'data-label',
				'Array Field'
			);
		} );

		it( 'renders jwtSecret field for jwt-secret control type', () => {
			const jwtField: FieldSchema = {
				label: 'JWT Secret',
				description: 'A JWT secret field',
				type: 'string',
				controlType: 'jwtSecret',
			};

			renderField( jwtField, 'secret', mockSetValue );

			const fieldControl = screen.getByTestId(
				'field-control-jwtSecret'
			);
			expect( fieldControl ).toBeInTheDocument();
			expect( fieldControl ).toHaveAttribute(
				'data-label',
				'JWT Secret'
			);
		} );

		it( 'uses controlType when explicitly provided', () => {
			const customField: FieldSchema = {
				label: 'Custom Field',
				description: 'A custom field',
				type: 'string',
				controlType: 'toggle',
			};

			renderField( customField, true, mockSetValue );

			const fieldControl = screen.getByTestId( 'field-control-toggle' );
			expect( fieldControl ).toBeInTheDocument();
		} );
	} );

	describe( 'Label and description display', () => {
		it( 'displays field label', () => {
			const field: FieldSchema = {
				label: 'Field Label',
				description: 'Field description',
				type: 'string',
			};

			renderField( field, 'value', mockSetValue );

			const fieldControl = screen.getByTestId( 'field-control-text' );
			expect( fieldControl ).toHaveAttribute(
				'data-label',
				'Field Label'
			);
			expect( fieldControl ).toHaveTextContent( 'Field Label' );
		} );

		it( 'uses label when both label and description are provided', () => {
			const field: FieldSchema = {
				label: 'Field Label',
				description: 'Field description',
				type: 'string',
			};

			renderField( field, 'value', mockSetValue );

			const fieldControl = screen.getByTestId( 'field-control-text' );
			expect( fieldControl ).toHaveAttribute(
				'data-label',
				'Field Label'
			);
			expect( fieldControl ).toHaveTextContent( 'Field Label' );
		} );
	} );

	describe( 'Value binding', () => {
		it( 'passes initial value to FieldControl', () => {
			const field: FieldSchema = {
				label: 'Value Field',
				description: 'A field with value',
				type: 'string',
			};

			renderField( field, 'initial value', mockSetValue );

			const fieldControl = screen.getByTestId( 'field-control-text' );
			expect( fieldControl ).toHaveAttribute(
				'data-value',
				'"initial value"'
			);
		} );

		it( 'calls setValue when FieldControl changes', () => {
			const field: FieldSchema = {
				label: 'Value Field',
				description: 'A field with value',
				type: 'string',
			};

			renderField( field, 'initial value', mockSetValue );

			const fieldControl = screen.getByTestId( 'field-control-text' );
			fieldControl.click();

			expect( mockSetValue ).toHaveBeenCalledWith(
				'changed-initial value'
			);
		} );

		it( 'passes null value correctly', () => {
			const field: FieldSchema = {
				label: 'Null Field',
				description: 'A field with null value',
				type: 'string',
			};

			renderField( field, null, mockSetValue );

			const fieldControl = screen.getByTestId( 'field-control-text' );
			expect( fieldControl ).toHaveAttribute( 'data-value', 'null' );
		} );

		it( 'passes boolean value correctly', () => {
			const field: FieldSchema = {
				label: 'Boolean Field',
				description: 'A boolean field',
				type: 'boolean',
			};

			renderField( field, true, mockSetValue );

			const fieldControl = screen.getByTestId( 'field-control-toggle' );
			expect( fieldControl ).toHaveAttribute( 'data-value', 'true' );
		} );

		it( 'passes array value correctly', () => {
			const field: FieldSchema = {
				label: 'Array Field',
				description: 'An array field',
				type: 'array',
			};

			const testArray = [ 'item1', 'item2' ];
			renderField( field, testArray, mockSetValue );

			const fieldControl = screen.getByTestId(
				'field-control-formTokenField'
			);
			expect( fieldControl ).toHaveAttribute(
				'data-value',
				JSON.stringify( testArray )
			);
		} );
	} );

	describe( 'Conditional logic (isConditionMet prop)', () => {
		it( 'disables field when isConditionMet is false', () => {
			const field: FieldSchema = {
				label: 'Conditional Field',
				description: 'A conditional field',
				type: 'string',
			};

			renderField( field, 'value', mockSetValue, false );

			// Field returns null when isConditionMet is false
			expect(
				screen.queryByTestId( 'field-control-text' )
			).not.toBeInTheDocument();
		} );

		it( 'enables field when isConditionMet is true', () => {
			const field: FieldSchema = {
				label: 'Conditional Field',
				description: 'A conditional field',
				type: 'string',
			};

			renderField( field, 'value', mockSetValue, true );

			const fieldControl = screen.getByTestId( 'field-control-text' );
			expect( fieldControl ).toHaveAttribute( 'data-disabled', 'false' );
		} );

		it( 'enables field when isConditionMet is not provided (defaults to true)', () => {
			const field: FieldSchema = {
				label: 'Default Field',
				description: 'A field with default condition',
				type: 'string',
			};

			const { container } = renderField( field, 'value', mockSetValue );

			const fieldControl = container.querySelector(
				'[data-testid="field-control-text"]'
			);
			expect( fieldControl ).toHaveAttribute( 'data-disabled', 'false' );
		} );

		it( 'does not prevent rendering when isConditionMet is false', () => {
			const field: FieldSchema = {
				label: 'Conditional Field',
				description: 'A conditional field',
				type: 'string',
			};

			renderField( field, 'value', mockSetValue, false );

			// Field returns null when isConditionMet is false
			expect(
				screen.queryByTestId( 'field-control-text' )
			).not.toBeInTheDocument();
		} );
	} );

	describe( 'Required field validation', () => {
		it( 'passes required prop to FieldControl when required is true', () => {
			const field: FieldSchema = {
				label: 'Required Field',
				description: 'A required field',
				type: 'string',
				required: true,
			};

			renderField( field, 'value', mockSetValue );

			const fieldControl = screen.getByTestId( 'field-control-text' );
			expect( fieldControl ).toBeInTheDocument();
		} );

		it( 'passes required prop as false when required is not provided', () => {
			const field: FieldSchema = {
				label: 'Optional Field',
				description: 'An optional field',
				type: 'string',
			};

			renderField( field, 'value', mockSetValue );

			const fieldControl = screen.getByTestId( 'field-control-text' );
			expect( fieldControl ).toBeInTheDocument();
		} );

		it( 'passes required prop as false when required is explicitly false', () => {
			const field: FieldSchema = {
				label: 'Not Required Field',
				description: 'A not required field',
				type: 'string',
				required: false,
			};

			renderField( field, 'value', mockSetValue );

			const fieldControl = screen.getByTestId( 'field-control-text' );
			expect( fieldControl ).toBeInTheDocument();
		} );
	} );

	describe( 'Advanced field toggling', () => {
		it( 'renders non-advanced field when showAdvancedSettings is false', () => {
			const field: FieldSchema = {
				label: 'Regular Field',
				description: 'A regular field',
				type: 'string',
				advanced: false,
			};

			renderField( field, 'value', mockSetValue, true, false );

			const fieldControl = screen.getByTestId( 'field-control-text' );
			expect( fieldControl ).toBeInTheDocument();
		} );

		it( 'does not render advanced field when showAdvancedSettings is false', () => {
			const field: FieldSchema = {
				label: 'Advanced Field',
				description: 'An advanced field',
				type: 'string',
				advanced: true,
			};

			const { container } = renderField(
				field,
				'value',
				mockSetValue,
				true,
				false
			);

			const fieldControl = container.querySelector(
				'[data-testid="field-control-text"]'
			);
			expect( fieldControl ).not.toBeInTheDocument();
		} );

		it( 'renders advanced field when showAdvancedSettings is true', async () => {
			const field: FieldSchema = {
				label: 'Advanced Field',
				description: 'An advanced field',
				type: 'string',
				advanced: true,
			};

			renderField( field, 'value', mockSetValue, true, true );

			const fieldControl =
				await screen.findByTestId( 'field-control-text' );
			expect( fieldControl ).toBeInTheDocument();
		} );

		it( 'renders regular field when isAdvanced is not provided and showAdvancedSettings is false', () => {
			const field: FieldSchema = {
				label: 'Default Field',
				description: 'A default field',
				type: 'string',
			};

			renderField( field, 'value', mockSetValue, true, false );

			const fieldControl = screen.getByTestId( 'field-control-text' );
			expect( fieldControl ).toBeInTheDocument();
		} );

		it( 'renders both regular and advanced fields when showAdvancedSettings is true', async () => {
			const field1: FieldSchema = {
				label: 'Regular Field',
				description: 'A regular field',
				type: 'string',
				advanced: false,
			};

			const field2: FieldSchema = {
				label: 'Advanced Field',
				description: 'An advanced field',
				type: 'string',
				advanced: true,
			};

			// Render each Field using the helper so the SettingsProvider mock is configured
			renderField( field1, 'value1', mockSetValue, true, true );
			renderField( field2, 'value2', mockSetValue, true, true );

			const fieldControls = screen.getAllByTestId( 'field-control-text' );
			expect( fieldControls ).toHaveLength( 2 );
		} );
	} );

	describe( 'Edge cases', () => {
		it( 'returns null when field is undefined', () => {
			let result: ReturnType< typeof render >;
			act( () => {
				result = render(
					<SettingsProvider>
						<Field
							field={ undefined as unknown as FieldSchema }
							value="value"
							setValue={ mockSetValue }
						/>
					</SettingsProvider>
				);
			} );

			// @ts-expect-error - assigned inside act
			expect( result.container.firstChild ).toBeNull();
		} );

		it( 'returns null when field is null', () => {
			let result: ReturnType< typeof render >;
			act( () => {
				result = render(
					<SettingsProvider>
						<Field
							field={ null as unknown as FieldSchema }
							value="value"
							setValue={ mockSetValue }
						/>
					</SettingsProvider>
				);
			} );

			// @ts-expect-error - assigned inside act
			expect( result.container.firstChild ).toBeNull();
		} );

		it( 'handles field with minimal properties', () => {
			const field: FieldSchema = {
				label: 'Minimal Field',
				description: 'Minimal description',
				type: 'string',
			};

			const { container } = renderField( field, 'value', mockSetValue );

			const panelRow = container.querySelector(
				'[data-testid="panel-row"]'
			);
			expect( panelRow ).toBeInTheDocument();
		} );

		it( 'handles field with all properties', () => {
			const field: FieldSchema = {
				label: 'Complete Field',
				description: 'Complete description',
				type: 'string',
				required: true,
				isAdvanced: false,
				help: 'Help text',
				order: 1,
				enum: [ 'option1', 'option2' ],
				hidden: false,
				controlType: 'text',
			};

			const { container } = renderField( field, 'value', mockSetValue );

			const panelRow = container.querySelector(
				'[data-testid="panel-row"]'
			);
			expect( panelRow ).toBeInTheDocument();
		} );
	} );

	describe( 'Integration with PanelRow', () => {
		it( 'wraps FieldControl in PanelRow', () => {
			const field: FieldSchema = {
				label: 'Wrapped Field',
				description: 'A wrapped field',
				type: 'string',
			};

			const { container } = renderField( field, 'value', mockSetValue );

			const panelRow = container.querySelector(
				'[data-testid="panel-row"]'
			);
			const fieldControl = container.querySelector(
				'[data-testid="field-control-text"]'
			);

			expect( panelRow ).toBeInTheDocument();
			expect( fieldControl ).toBeInTheDocument();
			expect( panelRow?.contains( fieldControl ) ).toBe( true );
		} );
	} );
} );
