import { render, screen, fireEvent } from '@testing-library/react';
import { vi, beforeEach, expect } from 'vitest';
import { FormTokenFieldControl } from '@/admin/components/ui/form-token-field';

vi.mock( '@wordpress/components', () => ( {
	FormTokenField: ( {
		value,
		onChange,
		tokenizeOnSpace,
		label,
		help,
		disabled,
		...props
	}: Record< string, unknown > ) => (
		<input
			data-testid="form-token-field-input"
			data-value={ JSON.stringify( value ) }
			data-tokenize-on-space={ String( tokenizeOnSpace ) }
			data-label={ String( label || '' ) }
			data-help={ String( help || '' ) }
			data-disabled={ String( disabled || false ) }
			onChange={
				onChange as unknown as React.ChangeEventHandler< HTMLInputElement >
			}
			{ ...props }
		/>
	),
} ) );

vi.mock( '@wordpress/compose', () => ( {
	useInstanceId: ( component: string ) => `${ component }-instance-id`,
} ) );

describe( 'FormTokenField Component', () => {
	beforeEach( () => {
		vi.clearAllMocks();
	} );

	it( 'renders with correct props', () => {
		const { container } = render(
			<FormTokenFieldControl
				help="Field help text"
				value={ [ 'token1', 'token2' ] }
			/>
		);

		expect(
			container.querySelector( '.components-form-token-field-control' )
		).toBeInTheDocument();
		expect( screen.getByText( 'Field help text' ) ).toBeInTheDocument();
	} );

	it( 'renders help text when provided', () => {
		const { container } = render(
			<FormTokenFieldControl
				help="Custom help text"
				value={ [ 'token1', 'token2' ] }
			/>
		);

		const helpElement = container.querySelector(
			'.components-form-token-field__help'
		);
		expect( helpElement ).toBeInTheDocument();
		expect( helpElement ).toHaveTextContent( 'Custom help text' );
	} );

	it( 'renders without help when not provided', () => {
		const { container } = render(
			<FormTokenFieldControl value={ [ 'token1', 'token2' ] } />
		);

		const helpElement = container.querySelector(
			'.components-form-token-field__help'
		);
		expect( helpElement ).not.toBeInTheDocument();
	} );

	it( 'tokenizeOnSpace is enabled when provided', () => {
		render(
			<FormTokenFieldControl
				tokenizeOnSpace
				value={ [ 'token1', 'token2' ] }
			/>
		);

		const input = screen.getByTestId( 'form-token-field-input' );
		expect( input ).toHaveAttribute( 'data-tokenize-on-space', 'true' );
	} );

	it( 'tokenizeOnSpace is disabled when false', () => {
		render(
			<FormTokenFieldControl
				tokenizeOnSpace={ false }
				value={ [ 'token1', 'token2' ] }
			/>
		);

		const input = screen.getByTestId( 'form-token-field-input' );
		expect( input ).toHaveAttribute( 'data-tokenize-on-space', 'false' );
	} );

	it( 'displays value array correctly', () => {
		render(
			<FormTokenFieldControl value={ [ 'token1', 'token2', 'token3' ] } />
		);

		const input = screen.getByTestId( 'form-token-field-input' );
		const value = JSON.parse(
			input.getAttribute( 'data-value' ) || '[]'
		) as string[];
		expect( value ).toEqual( [ 'token1', 'token2', 'token3' ] );
	} );

	it( 'onChange callback triggered with new tokens', () => {
		const handleChange = vi.fn();

		render(
			<FormTokenFieldControl
				value={ [ 'token1', 'token2' ] }
				onChange={
					handleChange as unknown as ( tokens: unknown ) => void
				}
			/>
		);

		const input = screen.getByTestId( 'form-token-field-input' );

		fireEvent.change( input, {
			target: { value: 'token1,token2,token3' },
		} );

		expect( handleChange ).toHaveBeenCalled();
	} );

	it( 'passes label and disabled props correctly', () => {
		render(
			<FormTokenFieldControl
				label="Test Label"
				help="Test Help"
				disabled
				value={ [ 'token1' ] }
			/>
		);

		const input = screen.getByTestId( 'form-token-field-input' );
		expect( input ).toHaveAttribute( 'data-label', 'Test Label' );
		expect( input ).toHaveAttribute( 'data-disabled', 'true' );
		// help is not passed to FormTokenField, it's rendered separately
		expect( screen.getByText( 'Test Help' ) ).toBeInTheDocument();
	} );

	it( 'handles empty value array', () => {
		render( <FormTokenFieldControl value={ [] } /> );

		const input = screen.getByTestId( 'form-token-field-input' );
		const value = JSON.parse(
			input.getAttribute( 'data-value' ) || '[]'
		) as string[];
		expect( value ).toEqual( [] );
	} );

	it( 'handles single token', () => {
		render( <FormTokenFieldControl value={ [ 'singleToken' ] } /> );

		const input = screen.getByTestId( 'form-token-field-input' );
		const value = JSON.parse(
			input.getAttribute( 'data-value' ) || '[]'
		) as string[];
		expect( value ).toEqual( [ 'singleToken' ] );
	} );

	it( 'handles duplicate tokens', () => {
		render(
			<FormTokenFieldControl value={ [ 'token1', 'token1', 'token2' ] } />
		);

		const input = screen.getByTestId( 'form-token-field-input' );
		const value = JSON.parse(
			input.getAttribute( 'data-value' ) || '[]'
		) as string[];
		expect( value ).toEqual( [ 'token1', 'token1', 'token2' ] );
	} );

	it( 'handles non-string values in array', () => {
		render(
			<FormTokenFieldControl
				value={ [ 'string', 123, true, null ] as unknown as string[] }
			/>
		);

		const input = screen.getByTestId( 'form-token-field-input' );
		const value = JSON.parse( input.getAttribute( 'data-value' ) || '[]' );
		expect( value ).toEqual( [ 'string', 123, true, null ] );
	} );
} );
