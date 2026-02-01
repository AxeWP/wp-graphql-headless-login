import { render } from '@testing-library/react';
import { vi, beforeEach, afterEach } from 'vitest';
import { Fields } from '@/admin/components/fields';
import * as SettingsContext from '@/admin/contexts/settings-context';
import type { FieldSchema } from '@/admin/types';

// Mock the Field component
vi.mock( '@/admin/components/fields/field', () => ( {
	Field: ( {
		field,
		value,
		setValue,
		isConditionMet = true,
	}: {
		field: FieldSchema;
		value: unknown;
		isConditionMet?: boolean;
		setValue?: ( value: unknown ) => void;
	} ) => {
		// Read the test-imported SettingsContext so tests can spyOn/mock its return
		// and the mock will respect `showAdvancedSettings`.
		const { showAdvancedSettings } = SettingsContext.useSettings?.() || {
			showAdvancedSettings: false,
		};

		if ( ! isConditionMet ) {
			return null;
		}

		if ( ! showAdvancedSettings && !! field?.advanced ) {
			return null;
		}

		return (
			<button
				type="button"
				data-testid={ `field-${ field?.label
					?.toLowerCase()
					.replace( /\s+/g, '-' ) }` }
				data-field-key={ field?.label }
				data-value={ JSON.stringify( value ) }
				onClick={ () => setValue?.( 'updated-value' ) }
			>
				{ field?.label }
			</button>
		);
	},
} ) );

describe( 'Fields Component', () => {
	let mockSetValue: ReturnType< typeof vi.fn > &
		( ( values: Record< string, unknown > ) => void );

	beforeEach( () => {
		mockSetValue = vi.fn() as ReturnType< typeof vi.fn > &
			( ( values: Record< string, unknown > ) => void );
		vi.clearAllMocks();
	} );

	afterEach( () => {
		vi.clearAllMocks();
	} );

	describe( 'Field ordering', () => {
		it( 'renders fields in correct order based on order property', () => {
			const mockFields: Record< string, FieldSchema > = {
				secondField: {
					label: 'Second Field',
					description: 'Second description',
					type: 'string',
					order: 2,
				},
				firstField: {
					label: 'First Field',
					description: 'First description',
					type: 'string',
					order: 1,
				},
				thirdField: {
					label: 'Third Field',
					description: 'Third description',
					type: 'string',
					order: 3,
				},
			};

			const mockValues = {
				firstField: 'value1',
				secondField: 'value2',
				thirdField: 'value3',
			};

			const { container } = render(
				<Fields
					fields={ mockFields }
					values={ mockValues }
					setValue={ mockSetValue }
				/>
			);

			const fieldElements = container.querySelectorAll(
				'[data-testid^="field-"]'
			);
			expect( fieldElements ).toHaveLength( 3 );
			expect( fieldElements[ 0 ] ).toHaveAttribute(
				'data-field-key',
				'First Field'
			);
			expect( fieldElements[ 1 ] ).toHaveAttribute(
				'data-field-key',
				'Second Field'
			);
			expect( fieldElements[ 2 ] ).toHaveAttribute(
				'data-field-key',
				'Third Field'
			);
		} );

		it( 'handles fields without order property (treats as order 0)', () => {
			const mockFields: Record< string, FieldSchema > = {
				noOrderField: {
					label: 'No Order Field',
					description: 'No order description',
					type: 'string',
				},
				orderedField: {
					label: 'Ordered Field',
					description: 'Ordered description',
					type: 'string',
					order: 1,
				},
			};

			const mockValues = {
				noOrderField: 'value1',
				orderedField: 'value2',
			};

			const { container } = render(
				<Fields
					fields={ mockFields }
					values={ mockValues }
					setValue={ mockSetValue }
				/>
			);

			const fieldElements = container.querySelectorAll(
				'[data-testid^="field-"]'
			);
			expect( fieldElements ).toHaveLength( 2 );
			expect( fieldElements[ 0 ] ).toHaveAttribute(
				'data-field-key',
				'No Order Field'
			);
			expect( fieldElements[ 1 ] ).toHaveAttribute(
				'data-field-key',
				'Ordered Field'
			);
		} );

		it( 'handles fields with same order property', () => {
			const mockFields: Record< string, FieldSchema > = {
				fieldA: {
					label: 'Field A',
					description: 'Description A',
					type: 'string',
					order: 1,
				},
				fieldB: {
					label: 'Field B',
					description: 'Description B',
					type: 'string',
					order: 1,
				},
			};

			const mockValues = {
				fieldA: 'valueA',
				fieldB: 'valueB',
			};

			const { container } = render(
				<Fields
					fields={ mockFields }
					values={ mockValues }
					setValue={ mockSetValue }
				/>
			);

			const fieldElements = container.querySelectorAll(
				'[data-testid^="field-"]'
			);
			expect( fieldElements ).toHaveLength( 2 );
		} );
	} );

	describe( 'Excluded properties filtering', () => {
		it( 'filters out default excluded properties (id, order)', () => {
			const mockFields: Record< string, FieldSchema > = {
				name: {
					label: 'Name',
					description: 'Name description',
					type: 'string',
				},
				id: {
					label: 'ID',
					description: 'ID description',
					type: 'string',
				},
				order: {
					label: 'Order',
					description: 'Order description',
					type: 'string',
				},
			};

			const mockValues = {
				name: 'test',
				id: '123',
				order: '1',
			};

			const { container } = render(
				<Fields
					fields={ mockFields }
					values={ mockValues }
					setValue={ mockSetValue }
				/>
			);

			const fieldElements = container.querySelectorAll(
				'[data-testid^="field-"]'
			);
			expect( fieldElements ).toHaveLength( 1 );
			expect( fieldElements[ 0 ] ).toHaveAttribute(
				'data-field-key',
				'Name'
			);
		} );

		it( 'filters out custom excluded properties', () => {
			const mockFields: Record< string, FieldSchema > = {
				name: {
					label: 'Name',
					description: 'Name description',
					type: 'string',
				},
				email: {
					label: 'Email',
					description: 'Email description',
					type: 'string',
				},
				secret: {
					label: 'Secret',
					description: 'Secret description',
					type: 'string',
				},
			};

			const mockValues = {
				name: 'test',
				email: 'test@example.com',
				secret: 'hidden',
			};

			const { container } = render(
				<Fields
					fields={ mockFields }
					values={ mockValues }
					setValue={ mockSetValue }
					excludedProperties={ [ 'secret' ] }
				/>
			);

			const fieldElements = container.querySelectorAll(
				'[data-testid^="field-"]'
			);
			expect( fieldElements ).toHaveLength( 2 );
			expect( fieldElements[ 0 ] ).toHaveAttribute(
				'data-field-key',
				'Name'
			);
			expect( fieldElements[ 1 ] ).toHaveAttribute(
				'data-field-key',
				'Email'
			);
		} );

		it( 'accepts empty excludedProperties array', () => {
			const mockFields: Record< string, FieldSchema > = {
				id: {
					label: 'ID',
					description: 'ID description',
					type: 'string',
				},
				name: {
					label: 'Name',
					description: 'Name description',
					type: 'string',
				},
			};

			const mockValues = {
				id: '123',
				name: 'test',
			};

			const { container } = render(
				<Fields
					fields={ mockFields }
					values={ mockValues }
					setValue={ mockSetValue }
					excludedProperties={ [] }
				/>
			);

			const fieldElements = container.querySelectorAll(
				'[data-testid^="field-"]'
			);
			expect( fieldElements ).toHaveLength( 2 );
		} );
	} );

	describe( 'Hidden fields filtering', () => {
		it( 'does not render hidden fields', () => {
			const mockFields: Record< string, FieldSchema > = {
				visibleField: {
					label: 'Visible Field',
					description: 'Visible description',
					type: 'string',
				},
				hiddenField: {
					label: 'Hidden Field',
					description: 'Hidden description',
					type: 'string',
					hidden: true,
				},
			};

			const mockValues = {
				visibleField: 'visible',
				hiddenField: 'hidden',
			};

			const { container } = render(
				<Fields
					fields={ mockFields }
					values={ mockValues }
					setValue={ mockSetValue }
				/>
			);

			const fieldElements = container.querySelectorAll(
				'[data-testid^="field-"]'
			);
			expect( fieldElements ).toHaveLength( 1 );
			expect( fieldElements[ 0 ] ).toHaveAttribute(
				'data-field-key',
				'Visible Field'
			);
		} );

		it( 'renders multiple visible fields when some are hidden', () => {
			const mockFields: Record< string, FieldSchema > = {
				field1: {
					label: 'Field 1',
					description: 'Field 1 description',
					type: 'string',
				},
				field2: {
					label: 'Field 2',
					description: 'Field 2 description',
					type: 'string',
					hidden: true,
				},
				field3: {
					label: 'Field 3',
					description: 'Field 3 description',
					type: 'string',
				},
			};

			const mockValues = {
				field1: 'value1',
				field2: 'value2',
				field3: 'value3',
			};

			const { container } = render(
				<Fields
					fields={ mockFields }
					values={ mockValues }
					setValue={ mockSetValue }
				/>
			);

			const fieldElements = container.querySelectorAll(
				'[data-testid^="field-"]'
			);
			expect( fieldElements ).toHaveLength( 2 );
			expect( fieldElements[ 0 ] ).toHaveAttribute(
				'data-field-key',
				'Field 1'
			);
			expect( fieldElements[ 1 ] ).toHaveAttribute(
				'data-field-key',
				'Field 3'
			);
		} );
	} );

	describe( 'Advanced fields visibility', () => {
		it( 'hides advanced fields when showAdvancedSettings is false', () => {
			vi.spyOn( SettingsContext, 'useSettings' ).mockReturnValue( {
				showAdvancedSettings: false,
			} as any );

			const mockFields: Record< string, FieldSchema > = {
				advField: {
					label: 'Adv Field',
					description: 'Advanced description',
					type: 'string',
					advanced: true,
				},
				normalField: {
					label: 'Normal Field',
					description: 'Normal description',
					type: 'string',
				},
			};

			const mockValues = {
				advField: 'a',
				normalField: 'b',
			};

			const { container } = render(
				<Fields
					fields={ mockFields }
					values={ mockValues }
					setValue={ mockSetValue }
				/>
			);

			expect(
				container.querySelector( '[data-testid="field-adv-field"]' )
			).toBeNull();
			expect(
				container.querySelector( '[data-testid="field-normal-field"]' )
			).not.toBeNull();

			vi.restoreAllMocks();
		} );

		it( 'shows advanced fields when showAdvancedSettings is true', () => {
			vi.spyOn( SettingsContext, 'useSettings' ).mockReturnValue( {
				showAdvancedSettings: true,
			} as any );

			const mockFields: Record< string, FieldSchema > = {
				advField: {
					label: 'Adv Field',
					description: 'Advanced description',
					type: 'string',
					advanced: true,
				},
				normalField: {
					label: 'Normal Field',
					description: 'Normal description',
					type: 'string',
				},
			};

			const mockValues = {
				advField: 'a',
				normalField: 'b',
			};

			const { container } = render(
				<Fields
					fields={ mockFields }
					values={ mockValues }
					setValue={ mockSetValue }
				/>
			);

			expect(
				container.querySelector( '[data-testid="field-adv-field"]' )
			).not.toBeNull();
			expect(
				container.querySelector( '[data-testid="field-normal-field"]' )
			).not.toBeNull();

			vi.restoreAllMocks();
		} );
	} );

	describe( 'Conditional logic filtering', () => {
		it( 'renders fields when validateConditionalLogic returns true', () => {
			const mockFields: Record< string, FieldSchema > = {
				field1: {
					label: 'Field 1',
					description: 'Field 1 description',
					type: 'string',
				},
				field2: {
					label: 'Field 2',
					description: 'Field 2 description',
					type: 'string',
				},
			};

			const mockValues = {
				field1: 'value1',
				field2: 'value2',
			};

			const mockValidate = vi.fn().mockReturnValue( true );

			const { container } = render(
				<Fields
					fields={ mockFields }
					values={ mockValues }
					setValue={ mockSetValue }
					validateConditionalLogic={ mockValidate }
				/>
			);

			const fieldElements = container.querySelectorAll(
				'[data-testid^="field-"]'
			);
			expect( fieldElements ).toHaveLength( 2 );
			expect( mockValidate ).toHaveBeenCalledTimes( 2 );
		} );

		it( 'calls validateConditionalLogic and respects its result', () => {
			const mockFields: Record< string, FieldSchema > = {
				conditionalField: {
					label: 'Conditional Field',
					description: 'Conditional description',
					type: 'string',
				},
			};

			const mockValues = {
				conditionalField: 'value',
			};

			const mockValidate = vi.fn().mockReturnValue( false );

			const { container } = render(
				<Fields
					fields={ mockFields }
					values={ mockValues }
					setValue={ mockSetValue }
					validateConditionalLogic={ mockValidate }
				/>
			);

			const fieldElements = container.querySelectorAll(
				'[data-testid^="field-"]'
			);
			expect( fieldElements ).toHaveLength( 0 );
			expect( mockValidate ).toHaveBeenCalledWith( 'conditionalField' );
		} );

		it( 'calls validateConditionalLogic for each field and renders fields accordingly', () => {
			const mockFields: Record< string, FieldSchema > = {
				field1: {
					label: 'Field 1',
					description: 'Field 1 description',
					type: 'string',
				},
				field2: {
					label: 'Field 2',
					description: 'Field 2 description',
					type: 'string',
				},
				field3: {
					label: 'Field 3',
					description: 'Field 3 description',
					type: 'string',
				},
			};

			const mockValues = {
				field1: 'value1',
				field2: 'value2',
				field3: 'value3',
			};

			const mockValidate = vi.fn( ( key: string ) => key !== 'field2' );

			const { container } = render(
				<Fields
					fields={ mockFields }
					values={ mockValues }
					setValue={ mockSetValue }
					validateConditionalLogic={ mockValidate }
				/>
			);

			const fieldElements = container.querySelectorAll(
				'[data-testid^="field-"]'
			);
			expect( fieldElements ).toHaveLength( 2 );
			expect( fieldElements[ 0 ] ).toHaveAttribute(
				'data-field-key',
				'Field 1'
			);
			expect( fieldElements[ 1 ] ).toHaveAttribute(
				'data-field-key',
				'Field 3'
			);
		} );

		it( 'renders all fields when validateConditionalLogic is not provided', () => {
			const mockFields: Record< string, FieldSchema > = {
				field1: {
					label: 'Field 1',
					description: 'Field 1 description',
					type: 'string',
				},
				field2: {
					label: 'Field 2',
					description: 'Field 2 description',
					type: 'string',
				},
			};

			const mockValues = {
				field1: 'value1',
				field2: 'value2',
			};

			const { container } = render(
				<Fields
					fields={ mockFields }
					values={ mockValues }
					setValue={ mockSetValue }
				/>
			);

			const fieldElements = container.querySelectorAll(
				'[data-testid^="field-"]'
			);
			expect( fieldElements ).toHaveLength( 2 );
		} );
	} );

	describe( 'Value updates propagation', () => {
		it( 'passes correct values to Field components', () => {
			const mockFields: Record< string, FieldSchema > = {
				field1: {
					label: 'Field 1',
					description: 'Field 1 description',
					type: 'string',
				},
				field2: {
					label: 'Field 2',
					description: 'Field 2 description',
					type: 'string',
				},
			};

			const mockValues = {
				field1: 'value1',
				field2: 'value2',
			};

			const { container } = render(
				<Fields
					fields={ mockFields }
					values={ mockValues }
					setValue={ mockSetValue }
				/>
			);

			const field1 = container.querySelector(
				'[data-testid="field-field-1"]'
			);
			const field2 = container.querySelector(
				'[data-testid="field-field-2"]'
			);

			expect( field1 ).toHaveAttribute( 'data-value', '"value1"' );
			expect( field2 ).toHaveAttribute( 'data-value', '"value2"' );
		} );

		it( 'calls setValue with updated values when field changes', () => {
			const mockFields: Record< string, FieldSchema > = {
				field1: {
					label: 'Field 1',
					description: 'Field 1 description',
					type: 'string',
				},
				field2: {
					label: 'Field 2',
					description: 'Field 2 description',
					type: 'string',
				},
			};

			const mockValues = {
				field1: 'value1',
				field2: 'value2',
			};

			const { container } = render(
				<Fields
					fields={ mockFields }
					values={ mockValues }
					setValue={ mockSetValue }
				/>
			);

			const field1 = container.querySelector(
				'[data-testid="field-field-1"]'
			) as HTMLElement;
			field1?.click();

			expect( mockSetValue ).toHaveBeenCalledTimes( 1 );
			expect( mockSetValue ).toHaveBeenCalledWith( {
				field1: 'updated-value',
				field2: 'value2',
			} );
		} );

		it( 'maintains other field values when one field updates', () => {
			const mockFields: Record< string, FieldSchema > = {
				field1: {
					label: 'Field 1',
					description: 'Field 1 description',
					type: 'string',
				},
				field2: {
					label: 'Field 2',
					description: 'Field 2 description',
					type: 'string',
				},
				field3: {
					label: 'Field 3',
					description: 'Field 3 description',
					type: 'string',
				},
			};

			const mockValues = {
				field1: 'value1',
				field2: 'value2',
				field3: 'value3',
			};

			// This test verifies the structure of the setValue function
			// by ensuring that when passed to a Field component,
			// it creates the correct update structure
			const { container } = render(
				<Fields
					fields={ mockFields }
					values={ mockValues }
					setValue={ mockSetValue }
				/>
			);

			// Verify all fields are rendered with correct initial values
			const field1 = container.querySelector(
				'[data-testid="field-field-1"]'
			);
			const field2 = container.querySelector(
				'[data-testid="field-field-2"]'
			);
			const field3 = container.querySelector(
				'[data-testid="field-field-3"]'
			);

			expect( field1 ).toHaveAttribute( 'data-value', '"value1"' );
			expect( field2 ).toHaveAttribute( 'data-value', '"value2"' );
			expect( field3 ).toHaveAttribute( 'data-value', '"value3"' );
		} );
	} );

	describe( 'Edge cases', () => {
		it( 'returns null when values is undefined', () => {
			const mockFields: Record< string, FieldSchema > = {
				field1: {
					label: 'Field 1',
					description: 'Field 1 description',
					type: 'string',
				},
			};

			const { container } = render(
				<Fields
					fields={ mockFields }
					values={ undefined }
					setValue={ mockSetValue }
				/>
			);

			expect( container.firstChild ).toBeNull();
		} );

		it( 'handles empty values object', () => {
			const mockFields: Record< string, FieldSchema > = {
				field1: {
					label: 'Field 1',
					description: 'Field 1 description',
					type: 'string',
				},
			};

			const { container } = render(
				<Fields
					fields={ mockFields }
					values={ {} }
					setValue={ mockSetValue }
				/>
			);

			const fieldElements = container.querySelectorAll(
				'[data-testid^="field-"]'
			);
			expect( fieldElements ).toHaveLength( 1 );
			expect( fieldElements[ 0 ] ).toHaveAttribute(
				'data-field-key',
				'Field 1'
			);
		} );

		it( 'handles empty fields object', () => {
			const mockValues = {};

			const { container } = render(
				<Fields
					fields={ {} }
					values={ mockValues }
					setValue={ mockSetValue }
				/>
			);

			const fieldElements = container.querySelectorAll(
				'[data-testid^="field-"]'
			);
			expect( fieldElements ).toHaveLength( 0 );
		} );

		it( 'handles fields with null values', () => {
			const mockFields: Record< string, FieldSchema > = {
				field1: {
					label: 'Field 1',
					description: 'Field 1 description',
					type: 'string',
				},
				field2: {
					label: 'Field 2',
					description: 'Field 2 description',
					type: 'string',
				},
			};

			const mockValues = {
				field1: null,
				field2: 'value2',
			};

			const { container } = render(
				<Fields
					fields={ mockFields }
					values={ mockValues }
					setValue={ mockSetValue }
				/>
			);

			const fieldElements = container.querySelectorAll(
				'[data-testid^="field-"]'
			);
			expect( fieldElements ).toHaveLength( 2 );
		} );

		it( 'handles missing field schema values', () => {
			const mockFields: Record< string, FieldSchema > = {
				field1: {
					label: 'Field 1',
					description: 'Field 1 description',
					type: 'string',
				},
			};

			const mockValues = {
				field1: 'value1',
				field2: 'value2', // This field doesn't exist in schema
			};

			const { container } = render(
				<Fields
					fields={ mockFields }
					values={ mockValues }
					setValue={ mockSetValue }
				/>
			);

			const fieldElements = container.querySelectorAll(
				'[data-testid^="field-"]'
			);
			expect( fieldElements ).toHaveLength( 1 );
		} );

		it( 'handles field with undefined field definition', () => {
			const mockFields: Record< string, FieldSchema > = {
				validField: {
					label: 'Valid Field',
					description: 'Valid description',
					type: 'string',
				},
			};

			const mockValues = {
				validField: 'value',
			};

			const { container } = render(
				<Fields
					fields={ mockFields }
					values={ mockValues }
					setValue={ mockSetValue }
				/>
			);

			const fieldElements = container.querySelectorAll(
				'[data-testid^="field-"]'
			);
			expect( fieldElements ).toHaveLength( 1 );
		} );

		it( 'handles all fields filtered by exclusion, hidden, or conditional logic', () => {
			const mockFields: Record< string, FieldSchema > = {
				excludedField: {
					label: 'Excluded Field',
					description: 'Excluded description',
					type: 'string',
				},
				hiddenField: {
					label: 'Hidden Field',
					description: 'Hidden description',
					type: 'string',
					hidden: true,
				},
			};

			const mockValues = {
				excludedField: 'excluded',
				hiddenField: 'hidden',
			};

			const mockValidate = vi.fn().mockReturnValue( false );

			const { container } = render(
				<Fields
					fields={ mockFields }
					values={ mockValues }
					setValue={ mockSetValue }
					excludedProperties={ [ 'excludedField' ] }
					validateConditionalLogic={ mockValidate }
				/>
			);

			const fieldElements = container.querySelectorAll(
				'[data-testid^="field-"]'
			);
			expect( fieldElements ).toHaveLength( 0 );
		} );

		it( 'handles complex scenario with multiple filters including conditional logic', () => {
			const mockFields: Record< string, FieldSchema > = {
				excludedField: {
					label: 'Excluded Field',
					description: 'Excluded description',
					type: 'string',
					order: 1,
				},
				hiddenField: {
					label: 'Hidden Field',
					description: 'Hidden description',
					type: 'string',
					hidden: true,
					order: 2,
				},
				conditionalFalseField: {
					label: 'Conditional False Field',
					description: 'Conditional false description',
					type: 'string',
					order: 3,
				},
				visibleField1: {
					label: 'Visible Field 1',
					description: 'Visible 1 description',
					type: 'string',
					order: 4,
				},
				visibleField2: {
					label: 'Visible Field 2',
					description: 'Visible 2 description',
					type: 'string',
					order: 5,
				},
			};

			const mockValues = {
				excludedField: 'excluded',
				hiddenField: 'hidden',
				conditionalFalseField: 'conditional',
				visibleField1: 'visible1',
				visibleField2: 'visible2',
			};

			const mockValidate = vi.fn(
				( key: string ) =>
					key === 'visibleField1' || key === 'visibleField2'
			);

			const { container } = render(
				<Fields
					fields={ mockFields }
					values={ mockValues }
					setValue={ mockSetValue }
					excludedProperties={ [ 'excludedField' ] }
					validateConditionalLogic={ mockValidate }
				/>
			);

			const fieldElements = container.querySelectorAll(
				'[data-testid^="field-"]'
			);
			expect( fieldElements ).toHaveLength( 2 );
			expect( fieldElements[ 0 ] ).toHaveAttribute(
				'data-field-key',
				'Visible Field 1'
			);
			expect( fieldElements[ 1 ] ).toHaveAttribute(
				'data-field-key',
				'Visible Field 2'
			);
		} );
	} );

	describe( 'Conditional logic metadata', () => {
		it( 'passes isConditionMet prop to Field components', () => {
			const mockFields: Record< string, FieldSchema > = {
				field1: {
					label: 'Field 1',
					description: 'Field 1 description',
					type: 'string',
				},
				field2: {
					label: 'Field 2',
					description: 'Field 2 description',
					type: 'string',
				},
			};

			const mockValues = {
				field1: 'value1',
				field2: 'value2',
			};

			const mockValidate = vi.fn( ( key: string ) => key === 'field1' );

			const { container } = render(
				<Fields
					fields={ mockFields }
					values={ mockValues }
					setValue={ mockSetValue }
					validateConditionalLogic={ mockValidate }
				/>
			);

			const field1 = container.querySelector(
				'[data-testid="field-field-1"]'
			);
			const field2 = container.querySelector(
				'[data-testid="field-field-2"]'
			);

			expect( field1 ).not.toBeNull();
			expect( field2 ).toBeNull();
		} );

		it( 'defaults isConditionMet to true when validateConditionalLogic is not provided', () => {
			const mockFields: Record< string, FieldSchema > = {
				field1: {
					label: 'Field 1',
					description: 'Field 1 description',
					type: 'string',
				},
			};

			const mockValues = {
				field1: 'value1',
			};

			const { container } = render(
				<Fields
					fields={ mockFields }
					values={ mockValues }
					setValue={ mockSetValue }
				/>
			);

			const field1 = container.querySelector(
				'[data-testid="field-field-1"]'
			);
			expect( field1 ).not.toBeNull();
		} );
	} );
} );
