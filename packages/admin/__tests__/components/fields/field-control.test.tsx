import { render, screen } from '@testing-library/react';
import { vi, describe, it, expect } from 'vitest';
import { FieldControl } from '@/admin/components/fields/field-control';
import { SettingsProvider } from '@/admin/contexts/settings-context';
import type { FieldSchema } from '@/admin/types';

// Mock WordPress components
vi.mock( '@wordpress/components', () => ( {
	BaseControl: ( { children }: { children: React.ReactNode } ) => (
		<div data-testid="base-control">{ children }</div>
	),
	Button: ( {
		children,
		icon,
		...rest
	}: {
		children?: React.ReactNode;
		icon?: string;
		[ key: string ]: unknown;
	} ) => (
		<button
			data-testid="button"
			data-icon={ String( icon || '' ) }
			{ ...rest }
		>
			{ String( children ) }
		</button>
	),
	SelectControl: ( {
		label,
		help,
		disabled,
		value,
		onChange,
		options,
		required,
		...rest
	}: Record< string, unknown > ) => (
		<div
			data-testid="select-control"
			data-label={ String( label || '' ) }
			data-help={ String( help || '' ) }
			data-disabled={ String( disabled || false ) }
			data-value={ String( value || '' ) }
			data-required={ String( required || false ) }
			data-options={ JSON.stringify( options ) }
			data-rest={ JSON.stringify( rest ) }
		>
			Select Control: { String( label ) }
		</div>
	),
	TextControl: ( {
		label,
		help,
		disabled,
		value,
		onChange,
		type,
		required,
		...rest
	}: Record< string, unknown > ) => (
		<div
			data-testid="text-control"
			data-label={ String( label || '' ) }
			data-help={ String( help || '' ) }
			data-disabled={ String( disabled || false ) }
			data-value={ String( value || '' ) }
			data-type={ String( type || 'text' ) }
			data-required={ String( required || false ) }
			data-rest={ JSON.stringify( rest ) }
		>
			Text Control: { String( label ) }
		</div>
	),
	ToggleControl: ( {
		label,
		help,
		disabled,
		checked,
		onChange,
		required,
		...rest
	}: Record< string, unknown > ) => (
		<div
			data-testid="toggle-control"
			data-label={ String( label || '' ) }
			data-help={ String( help || '' ) }
			data-disabled={ String( disabled || false ) }
			data-checked={ String( checked || false ) }
			data-required={ String( required || false ) }
			data-rest={ JSON.stringify( rest ) }
		>
			Toggle Control: { String( label ) }
		</div>
	),
} ) );

// Mock FormTokenFieldControl
vi.mock( '@/admin/components/ui/form-token-field', () => ( {
	FormTokenFieldControl: ( {
		label,
		help,
		disabled,
		value,
		onChange,
		tokenizeOnSpace,
		required,
		...rest
	}: Record< string, unknown > ) => (
		<div
			data-testid="form-token-field-control"
			data-label={ String( label || '' ) }
			data-help={ String( help || '' ) }
			data-disabled={ String( disabled || false ) }
			data-value={ JSON.stringify( value ) }
			data-tokenize-on-space={ String( tokenizeOnSpace || false ) }
			data-required={ String( required || false ) }
			data-rest={ JSON.stringify( rest ) }
		>
			Form Token Field Control: { String( label ) }
		</div>
	),
} ) );

// Mock JwtSecretControl with the correct path
vi.mock( '@/admin/components/fields/jwt-secret-control', () => ( {
	JwtSecretControl: ( {
		label,
		help,
		...rest
	}: Record< string, unknown > ) => (
		<div
			data-testid="jwt-secret-control"
			data-label={ String( label || '' ) }
			data-help={ String( help || '' ) }
			data-rest={ JSON.stringify( rest ) }
		>
			JWT Secret Control: { String( label ) }
		</div>
	),
} ) );

// Mock api-fetch to prevent unhandled rejections
vi.mock( '@wordpress/api-fetch', () => ( {
	default: vi.fn().mockResolvedValue( {} ),
} ) );

describe( 'FieldControl Component', () => {
	const mockOnChange = vi.fn() as ( value: unknown ) => void;

	beforeEach( () => {
		vi.clearAllMocks();
	} );

	describe( 'Text control rendering', () => {
		it( 'renders text control with correct props', () => {
			const props: FieldSchema & {
				value: unknown;
				onChange: ( value: unknown ) => void;
			} = {
				label: 'Test Field',
				description: 'Test description',
				type: 'string',
				value: 'test value',
				onChange: mockOnChange,
			};

			render( <FieldControl { ...props } /> );

			const textControl = screen.getByTestId( 'text-control' );
			expect( textControl ).toBeInTheDocument();
			expect( textControl ).toHaveAttribute( 'data-label', 'Test Field' );
			expect( textControl ).toHaveAttribute( 'data-value', 'test value' );
			expect( textControl ).toHaveAttribute( 'data-type', 'text' );
		} );

		it( 'uses description as label when label is missing', () => {
			const props: FieldSchema & {
				value: unknown;
				onChange: ( value: unknown ) => void;
			} = {
				description: 'Description as label',
				label: '',
				type: 'string',
				value: 'test value',
				onChange: mockOnChange,
			};

			render( <FieldControl { ...props } /> );

			const textControl = screen.getByTestId( 'text-control' );
			expect( textControl ).toHaveAttribute(
				'data-label',
				'Description as label'
			);
		} );

		it( 'passes help text correctly', () => {
			const props: FieldSchema & {
				value: unknown;
				onChange: ( value: unknown ) => void;
			} = {
				label: 'Test Field',
				description: 'Test description',
				type: 'string',
				value: 'test value',
				onChange: mockOnChange,
				help: 'Help text',
			};

			render( <FieldControl { ...props } /> );

			const textControl = screen.getByTestId( 'text-control' );
			expect( textControl ).toHaveAttribute( 'data-help', 'Help text' );
		} );

		it( 'passes disabled state correctly', () => {
			const props: FieldSchema & {
				value: unknown;
				onChange: ( value: unknown ) => void;
				disabled: boolean;
			} = {
				label: 'Test Field',
				description: 'Test description',
				type: 'string',
				value: 'test value',
				onChange: mockOnChange,
				disabled: true,
			};

			render( <FieldControl { ...props } /> );

			const textControl = screen.getByTestId( 'text-control' );
			expect( textControl ).toHaveAttribute( 'data-disabled', 'true' );
		} );

		it( 'passes required flag correctly', () => {
			const props: FieldSchema & {
				value: unknown;
				onChange: ( value: unknown ) => void;
			} = {
				label: 'Test Field',
				description: 'Test description',
				type: 'string',
				value: 'test value',
				onChange: mockOnChange,
				required: true,
			};

			render( <FieldControl { ...props } /> );

			const textControl = screen.getByTestId( 'text-control' );
			expect( textControl ).toHaveAttribute( 'data-required', 'true' );
		} );
	} );

	describe( 'Integer type handling', () => {
		it( 'uses number input type for integer fields', () => {
			const props: FieldSchema & {
				value: unknown;
				onChange: ( value: unknown ) => void;
			} = {
				label: 'Integer Field',
				description: 'Integer description',
				type: 'integer',
				value: '42',
				onChange: mockOnChange,
			};

			render( <FieldControl { ...props } /> );

			const textControl = screen.getByTestId( 'text-control' );
			expect( textControl ).toHaveAttribute( 'data-type', 'number' );
		} );

		it( 'parses integer value correctly', () => {
			const props: FieldSchema & {
				value: unknown;
				onChange: ( value: unknown ) => void;
			} = {
				label: 'Integer Field',
				description: 'Integer description',
				type: 'integer',
				value: '42',
				onChange: mockOnChange,
			};

			render( <FieldControl { ...props } /> );

			const textControl = screen.getByTestId( 'text-control' );
			expect( textControl ).toHaveAttribute( 'data-value', '42' );
		} );

		it( 'handles empty integer value', () => {
			const props: FieldSchema & {
				value: unknown;
				onChange: ( value: unknown ) => void;
			} = {
				label: 'Integer Field',
				description: 'Integer description',
				type: 'integer',
				value: null,
				onChange: mockOnChange,
			};

			render( <FieldControl { ...props } /> );

			const textControl = screen.getByTestId( 'text-control' );
			expect( textControl ).toHaveAttribute( 'data-value', '' );
		} );
	} );

	describe( 'Toggle control rendering', () => {
		it( 'renders toggle control with correct props', () => {
			const props: FieldSchema & {
				value: unknown;
				onChange: ( value: unknown ) => void;
			} = {
				label: 'Toggle Field',
				description: 'Toggle description',
				type: 'boolean',
				value: true,
				onChange: mockOnChange,
			};

			render( <FieldControl { ...props } /> );

			const toggleControl = screen.getByTestId( 'toggle-control' );
			expect( toggleControl ).toBeInTheDocument();
			expect( toggleControl ).toHaveAttribute(
				'data-label',
				'Toggle Field'
			);
			expect( toggleControl ).toHaveAttribute( 'data-checked', 'true' );
		} );

		it( 'passes help text to toggle', () => {
			const props: FieldSchema & {
				value: unknown;
				onChange: ( value: unknown ) => void;
			} = {
				label: 'Toggle Field',
				description: 'Toggle description',
				type: 'boolean',
				value: false,
				onChange: mockOnChange,
				help: 'Toggle help text',
			};

			render( <FieldControl { ...props } /> );

			const toggleControl = screen.getByTestId( 'toggle-control' );
			expect( toggleControl ).toHaveAttribute(
				'data-help',
				'Toggle help text'
			);
		} );

		it( 'passes disabled state to toggle', () => {
			const props: FieldSchema & {
				value: unknown;
				onChange: ( value: unknown ) => void;
				disabled: boolean;
			} = {
				label: 'Toggle Field',
				description: 'Toggle description',
				type: 'boolean',
				value: true,
				onChange: mockOnChange,
				disabled: true,
			};

			render( <FieldControl { ...props } /> );

			const toggleControl = screen.getByTestId( 'toggle-control' );
			expect( toggleControl ).toHaveAttribute( 'data-disabled', 'true' );
		} );

		it( 'passes required flag to toggle', () => {
			const props: FieldSchema & {
				value: unknown;
				onChange: ( value: unknown ) => void;
			} = {
				label: 'Toggle Field',
				description: 'Toggle description',
				type: 'boolean',
				value: true,
				onChange: mockOnChange,
				required: true,
			};

			render( <FieldControl { ...props } /> );

			const toggleControl = screen.getByTestId( 'toggle-control' );
			expect( toggleControl ).toHaveAttribute( 'data-required', 'true' );
		} );

		it( 'converts value to boolean for toggle', () => {
			const props: FieldSchema & {
				value: unknown;
				onChange: ( value: unknown ) => void;
			} = {
				label: 'Toggle Field',
				description: 'Toggle description',
				type: 'boolean',
				value: 'yes',
				onChange: mockOnChange,
			};

			render( <FieldControl { ...props } /> );

			const toggleControl = screen.getByTestId( 'toggle-control' );
			expect( toggleControl ).toHaveAttribute( 'data-checked', 'true' );
		} );
	} );

	describe( 'Select control rendering', () => {
		it( 'renders select control with correct props', () => {
			const props: FieldSchema & {
				value: unknown;
				onChange: ( value: unknown ) => void;
				enum: string[];
			} = {
				label: 'Select Field',
				description: 'Select description',
				type: 'string',
				controlType: 'select',
				value: 'option1',
				onChange: mockOnChange,
				enum: [ 'option1', 'option2', 'option3' ],
			};

			render( <FieldControl { ...props } /> );

			const selectControl = screen.getByTestId( 'select-control' );
			expect( selectControl ).toBeInTheDocument();
			expect( selectControl ).toHaveAttribute(
				'data-label',
				'Select Field'
			);
			expect( selectControl ).toHaveAttribute( 'data-value', 'option1' );
		} );

		it( 'maps enum values to options with proper capitalization', () => {
			const props: FieldSchema & {
				value: unknown;
				onChange: ( value: unknown ) => void;
				enum: string[];
			} = {
				label: 'Select Field',
				description: 'Select description',
				type: 'string',
				controlType: 'select',
				value: 'apple',
				onChange: mockOnChange,
				enum: [ 'apple', 'banana', 'cherry' ],
			};

			render( <FieldControl { ...props } /> );

			const selectControl = screen.getByTestId( 'select-control' );
			const options = JSON.parse(
				selectControl.getAttribute( 'data-options' ) || '[]'
			);
			expect( options ).toEqual( [
				{ label: 'Apple', value: 'apple' },
				{ label: 'Banana', value: 'banana' },
				{ label: 'Cherry', value: 'cherry' },
			] );
		} );

		it( 'handles empty enum array for select control', () => {
			const props: FieldSchema & {
				value: unknown;
				onChange: ( value: unknown ) => void;
				enum: string[];
			} = {
				label: 'Select Field',
				description: 'Select description',
				type: 'string',
				controlType: 'select',
				value: '',
				onChange: mockOnChange,
				enum: [],
			};

			render( <FieldControl { ...props } /> );

			const selectControl = screen.getByTestId( 'select-control' );
			const options = JSON.parse(
				selectControl.getAttribute( 'data-options' ) || '[]'
			);
			expect( options ).toEqual( [] );
		} );

		it( 'passes help text to select', () => {
			const props: FieldSchema & {
				value: unknown;
				onChange: ( value: unknown ) => void;
				enum: string[];
			} = {
				label: 'Select Field',
				description: 'Select description',
				type: 'string',
				controlType: 'select',
				value: 'option1',
				onChange: mockOnChange,
				enum: [ 'option1' ],
				help: 'Select help text',
			};

			render( <FieldControl { ...props } /> );

			const selectControl = screen.getByTestId( 'select-control' );
			expect( selectControl ).toHaveAttribute(
				'data-help',
				'Select help text'
			);
		} );

		it( 'passes disabled state to select', () => {
			const props: FieldSchema & {
				value: unknown;
				onChange: ( value: unknown ) => void;
				enum: string[];
				disabled: boolean;
			} = {
				label: 'Select Field',
				description: 'Select description',
				type: 'string',
				controlType: 'select',
				value: 'option1',
				onChange: mockOnChange,
				enum: [ 'option1' ],
				disabled: true,
			};

			render( <FieldControl { ...props } /> );

			const selectControl = screen.getByTestId( 'select-control' );
			expect( selectControl ).toHaveAttribute( 'data-disabled', 'true' );
		} );
	} );

	describe( 'FormTokenField control rendering', () => {
		it( 'renders formTokenField control with correct props', () => {
			const props: FieldSchema & {
				value: unknown;
				onChange: ( value: unknown ) => void;
			} = {
				label: 'Token Field',
				description: 'Token description',
				type: 'array',
				value: [ 'token1', 'token2' ],
				onChange: mockOnChange,
			};

			render( <FieldControl { ...props } /> );

			const formTokenFieldControl = screen.getByTestId(
				'form-token-field-control'
			);
			expect( formTokenFieldControl ).toBeInTheDocument();
			expect( formTokenFieldControl ).toHaveAttribute(
				'data-label',
				'Token Field'
			);
		} );

		it( 'handles array values correctly', () => {
			const props: FieldSchema & {
				value: unknown;
				onChange: ( value: unknown ) => void;
			} = {
				label: 'Token Field',
				description: 'Token description',
				type: 'array',
				value: [ 'value1', 'value2', 'value3' ],
				onChange: mockOnChange,
			};

			render( <FieldControl { ...props } /> );

			const formTokenFieldControl = screen.getByTestId(
				'form-token-field-control'
			);
			const value = JSON.parse(
				formTokenFieldControl.getAttribute( 'data-value' ) || '[]'
			);
			expect( value ).toEqual( [ 'value1', 'value2', 'value3' ] );
		} );

		it( 'converts non-array value to empty array', () => {
			const props: FieldSchema & {
				value: unknown;
				onChange: ( value: unknown ) => void;
			} = {
				label: 'Token Field',
				description: 'Token description',
				type: 'array',
				value: 'not an array',
				onChange: mockOnChange,
			};

			render( <FieldControl { ...props } /> );

			const formTokenFieldControl = screen.getByTestId(
				'form-token-field-control'
			);
			const value = JSON.parse(
				formTokenFieldControl.getAttribute( 'data-value' ) || '[]'
			);
			expect( value ).toEqual( [] );
		} );

		it( 'passes help text to formTokenField', () => {
			const props: FieldSchema & {
				value: unknown;
				onChange: ( value: unknown ) => void;
			} = {
				label: 'Token Field',
				description: 'Token description',
				type: 'array',
				value: [ 'token1' ],
				onChange: mockOnChange,
				help: 'Token help text',
			};

			render( <FieldControl { ...props } /> );

			const formTokenFieldControl = screen.getByTestId(
				'form-token-field-control'
			);
			expect( formTokenFieldControl ).toHaveAttribute(
				'data-help',
				'Token help text'
			);
		} );

		it( 'passes disabled state to formTokenField', () => {
			const props: FieldSchema & {
				value: unknown;
				onChange: ( value: unknown ) => void;
				disabled: boolean;
			} = {
				label: 'Token Field',
				description: 'Token description',
				type: 'array',
				value: [ 'token1' ],
				onChange: mockOnChange,
				disabled: true,
			};

			render( <FieldControl { ...props } /> );

			const formTokenFieldControl = screen.getByTestId(
				'form-token-field-control'
			);
			expect( formTokenFieldControl ).toHaveAttribute(
				'data-disabled',
				'true'
			);
		} );

		it( 'sets tokenizeOnSpace to true', () => {
			const props: FieldSchema & {
				value: unknown;
				onChange: ( value: unknown ) => void;
			} = {
				label: 'Token Field',
				description: 'Token description',
				type: 'array',
				value: [ 'token1' ],
				onChange: mockOnChange,
			};

			render( <FieldControl { ...props } /> );

			const formTokenFieldControl = screen.getByTestId(
				'form-token-field-control'
			);
			expect( formTokenFieldControl ).toHaveAttribute(
				'data-tokenize-on-space',
				'true'
			);
		} );
	} );

	describe( 'JwtSecret control rendering', () => {
		it( 'renders jwtSecret control with correct props', () => {
			const props: FieldSchema & {
				value: unknown;
				onChange: ( value: unknown ) => void;
			} = {
				label: 'JWT Secret',
				description: 'JWT Secret description',
				type: 'string',
				controlType: 'jwtSecret',
				value: 'secret',
				onChange: mockOnChange,
			} as FieldSchema & {
				value: unknown;
				onChange: ( value: unknown ) => void;
			};

			render(
				<SettingsProvider>
					<FieldControl { ...props } />
				</SettingsProvider>
			);

			const jwtSecretControl = screen.getByTestId( 'jwt-secret-control' );
			expect( jwtSecretControl ).toBeInTheDocument();
			expect( jwtSecretControl ).toHaveAttribute(
				'data-label',
				'JWT Secret'
			);
		} );

		it( 'passes help text to jwtSecret', () => {
			const props: FieldSchema & {
				value: unknown;
				onChange: ( value: unknown ) => void;
			} = {
				label: 'JWT Secret',
				description: 'JWT Secret description',
				type: 'string',
				controlType: 'jwtSecret',
				value: 'secret',
				onChange: mockOnChange,
				help: 'JWT Secret help',
			} as FieldSchema & {
				value: unknown;
				onChange: ( value: unknown ) => void;
			};

			render(
				<SettingsProvider>
					<FieldControl { ...props } />
				</SettingsProvider>
			);

			const jwtSecretControl = screen.getByTestId( 'jwt-secret-control' );
			expect( jwtSecretControl ).toHaveAttribute(
				'data-help',
				'JWT Secret help'
			);
		} );

		it( 'applies controlOverrides correctly for jwtSecret', () => {
			const props: FieldSchema & {
				value: unknown;
				onChange: ( value: unknown ) => void;
			} & { controlOverrides: Record< string, unknown > } = {
				label: 'JWT Secret',
				description: 'JWT Secret description',
				type: 'string',
				controlType: 'jwtSecret',
				value: 'secret',
				onChange: mockOnChange,
				controlOverrides: {
					className: 'custom-class',
				},
			} as FieldSchema & {
				value: unknown;
				onChange: ( value: unknown ) => void;
			} & { controlOverrides: Record< string, unknown > };

			render(
				<SettingsProvider>
					<FieldControl { ...props } />
				</SettingsProvider>
			);

			const jwtSecretControl = screen.getByTestId( 'jwt-secret-control' );
			const rest = JSON.parse(
				jwtSecretControl.getAttribute( 'data-rest' ) || '{}'
			);
			expect( rest ).toEqual( {
				className: 'custom-class',
			} );
		} );
	} );

	describe( 'getControlType mapping', () => {
		it( 'maps string type to text control', () => {
			const props: FieldSchema & {
				value: unknown;
				onChange: ( value: unknown ) => void;
			} = {
				label: 'String Field',
				description: 'String description',
				type: 'string',
				value: 'test',
				onChange: mockOnChange,
			};

			render( <FieldControl { ...props } /> );

			expect( screen.getByTestId( 'text-control' ) ).toBeInTheDocument();
		} );

		it( 'maps integer type to text control', () => {
			const props: FieldSchema & {
				value: unknown;
				onChange: ( value: unknown ) => void;
			} = {
				label: 'Integer Field',
				description: 'Integer description',
				type: 'integer',
				value: '42',
				onChange: mockOnChange,
			};

			render( <FieldControl { ...props } /> );

			expect( screen.getByTestId( 'text-control' ) ).toBeInTheDocument();
		} );

		it( 'maps boolean type to toggle control', () => {
			const props: FieldSchema & {
				value: unknown;
				onChange: ( value: unknown ) => void;
			} = {
				label: 'Boolean Field',
				description: 'Boolean description',
				type: 'boolean',
				value: true,
				onChange: mockOnChange,
			};

			render( <FieldControl { ...props } /> );

			expect(
				screen.getByTestId( 'toggle-control' )
			).toBeInTheDocument();
		} );

		it( 'maps array type to formTokenField control', () => {
			const props: FieldSchema & {
				value: unknown;
				onChange: ( value: unknown ) => void;
			} = {
				label: 'Array Field',
				description: 'Array description',
				type: 'array',
				value: [ 'item1' ],
				onChange: mockOnChange,
			};

			render( <FieldControl { ...props } /> );

			expect(
				screen.getByTestId( 'form-token-field-control' )
			).toBeInTheDocument();
		} );

		it( 'defaults to text control with warning for unknown field type', () => {
			const consoleWarn = vi.spyOn( console, 'warn' );

			const props: FieldSchema & {
				value: unknown;
				onChange: ( value: unknown ) => void;
			} = {
				label: 'Unknown Field',
				description: 'Unknown description',
				type: 'unknown_type' as unknown as FieldSchema[ 'type' ],
				value: 'test',
				onChange: mockOnChange,
			};

			render( <FieldControl { ...props } /> );

			expect( screen.getByTestId( 'text-control' ) ).toBeInTheDocument();
			expect( consoleWarn ).toHaveBeenCalledWith(
				'Unknown field type: unknown_type'
			);

			consoleWarn.mockRestore();
		} );
	} );

	describe( 'Unknown control type handling', () => {
		it( 'returns null for unknown control type', () => {
			const props = {
				label: 'Unknown Control',
				description: 'Unknown description',
				type: 'string',
				controlType: 'unknownControl',
				value: 'test',
				onChange: mockOnChange,
			} as FieldSchema & {
				value: unknown;
				onChange: ( value: unknown ) => void;
			};

			const { container } = render( <FieldControl { ...props } /> );

			expect( container.firstChild ).toBeNull();
		} );
	} );

	describe( 'Default value handling', () => {
		it( 'uses default value from rest when value is undefined', () => {
			const props: FieldSchema & {
				value: unknown;
				onChange: ( value: unknown ) => void;
			} & { default: string } = {
				label: 'Default Field',
				description: 'Default description',
				type: 'string',
				value: undefined,
				onChange: mockOnChange,
				default: 'default value',
			};

			render( <FieldControl { ...props } /> );

			const textControl = screen.getByTestId( 'text-control' );
			expect( textControl ).toHaveAttribute(
				'data-value',
				'default value'
			);
		} );

		it( 'uses provided value when it exists', () => {
			const props: FieldSchema & {
				value: unknown;
				onChange: ( value: unknown ) => void;
			} & { default: string } = {
				label: 'Default Field',
				description: 'Default description',
				type: 'string',
				value: 'provided value',
				onChange: mockOnChange,
				default: 'default value',
			};

			render( <FieldControl { ...props } /> );

			const textControl = screen.getByTestId( 'text-control' );
			expect( textControl ).toHaveAttribute(
				'data-value',
				'provided value'
			);
		} );
	} );

	describe( 'Control overrides', () => {
		it( 'applies controlOverrides correctly for text control', () => {
			const props: FieldSchema & {
				value: unknown;
				onChange: ( value: unknown ) => void;
			} & { controlOverrides: Record< string, unknown > } = {
				label: 'Override Field',
				description: 'Override description',
				type: 'string',
				value: 'test',
				onChange: mockOnChange,
				controlOverrides: {
					placeholder: 'Enter value',
					maxLength: 100,
				},
			};

			render( <FieldControl { ...props } /> );

			const textControl = screen.getByTestId( 'text-control' );
			const rest = JSON.parse(
				textControl.getAttribute( 'data-rest' ) || '{}'
			);
			expect( rest ).toEqual( {
				placeholder: 'Enter value',
				maxLength: 100,
			} );
		} );

		it( 'applies controlOverrides correctly for toggle control', () => {
			const props: FieldSchema & {
				value: unknown;
				onChange: ( value: unknown ) => void;
			} & { controlOverrides: Record< string, unknown > } = {
				label: 'Override Field',
				description: 'Override description',
				type: 'boolean',
				value: true,
				onChange: mockOnChange,
				controlOverrides: {
					showLabel: false,
				},
			};

			render( <FieldControl { ...props } /> );

			const toggleControl = screen.getByTestId( 'toggle-control' );
			const rest = JSON.parse(
				toggleControl.getAttribute( 'data-rest' ) || '{}'
			);
			expect( rest ).toEqual( {
				showLabel: false,
			} );
		} );

		it( 'applies controlOverrides correctly for select control', () => {
			const props: FieldSchema & {
				value: unknown;
				onChange: ( value: unknown ) => void;
				enum: string[];
			} & { controlOverrides: Record< string, unknown > } = {
				label: 'Override Field',
				description: 'Override description',
				type: 'string',
				controlType: 'select',
				value: 'option1',
				onChange: mockOnChange,
				enum: [ 'option1', 'option2' ],
				controlOverrides: {
					multiple: true,
				},
			};

			render( <FieldControl { ...props } /> );

			const selectControl = screen.getByTestId( 'select-control' );
			const rest = JSON.parse(
				selectControl.getAttribute( 'data-rest' ) || '{}'
			);
			expect( rest ).toEqual( {
				multiple: true,
			} );
		} );

		it( 'applies controlOverrides correctly for formTokenField', () => {
			const props: FieldSchema & {
				value: unknown;
				onChange: ( value: unknown ) => void;
			} & { controlOverrides: Record< string, unknown > } = {
				label: 'Override Field',
				description: 'Override description',
				type: 'array',
				value: [ 'token1' ],
				onChange: mockOnChange,
				controlOverrides: {
					maxLength: 5,
				},
			};

			render( <FieldControl { ...props } /> );

			const formTokenFieldControl = screen.getByTestId(
				'form-token-field-control'
			);
			const rest = JSON.parse(
				formTokenFieldControl.getAttribute( 'data-rest' ) || '{}'
			);
			expect( rest ).toEqual( {
				maxLength: 5,
			} );
		} );

		it( 'applies controlOverrides correctly for jwtSecret', () => {
			const props: FieldSchema & {
				value: unknown;
				onChange: ( value: unknown ) => void;
			} & { controlOverrides: Record< string, unknown > } = {
				label: 'JWT Secret',
				description: 'JWT Secret description',
				type: 'string',
				controlType: 'jwtSecret',
				value: 'secret',
				onChange: mockOnChange,
				controlOverrides: {
					className: 'custom-class',
				},
			};

			render( <FieldControl { ...props } /> );

			const jwtSecretControl = screen.getByTestId( 'jwt-secret-control' );
			const rest = JSON.parse(
				jwtSecretControl.getAttribute( 'data-rest' ) || '{}'
			);
			expect( rest ).toEqual( {
				className: 'custom-class',
			} );
		} );
	} );

	describe( 'Component overrides via controlType prop', () => {
		it( 'uses controlType override when provided', () => {
			const props: FieldSchema & {
				value: unknown;
				onChange: ( value: unknown ) => void;
			} = {
				label: 'Override Control',
				description: 'Override description',
				type: 'string',
				controlType: 'toggle',
				value: true,
				onChange: mockOnChange,
			} as FieldSchema & {
				value: unknown;
				onChange: ( value: unknown ) => void;
			};

			render( <FieldControl { ...props } /> );

			expect(
				screen.getByTestId( 'toggle-control' )
			).toBeInTheDocument();
		} );
	} );

	describe( 'Label and description fallback', () => {
		it( 'falls back to description when label is missing', () => {
			const props: FieldSchema & {
				value: unknown;
				onChange: ( value: unknown ) => void;
			} = {
				description: 'Fallback description',
				label: '',
				type: 'string',
				value: 'test',
				onChange: mockOnChange,
			};

			render( <FieldControl { ...props } /> );

			const textControl = screen.getByTestId( 'text-control' );
			expect( textControl ).toHaveAttribute(
				'data-label',
				'Fallback description'
			);
		} );

		it( 'uses label when both label and description are provided', () => {
			const props: FieldSchema & {
				value: unknown;
				onChange: ( value: unknown ) => void;
			} = {
				label: 'Primary Label',
				description: 'Secondary Description',
				type: 'string',
				value: 'test',
				onChange: mockOnChange,
			};

			render( <FieldControl { ...props } /> );

			const textControl = screen.getByTestId( 'text-control' );
			expect( textControl ).toHaveAttribute(
				'data-label',
				'Primary Label'
			);
		} );
	} );
} );
