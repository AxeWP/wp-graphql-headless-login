import { render } from '@testing-library/react';
import { Loading } from '@/admin/components/ui/loading';

vi.mock( '@wordpress/components', () => ( {
	Spinner: ( {
		className,
		style,
		...rest
	}: { className?: string; style?: Record< string, unknown > } & Record<
		string,
		unknown
	> ) => (
		<div
			className={ className }
			style={ style }
			data-testid="spinner"
			{ ...rest }
		/>
	),
} ) );

describe( 'Loading Component', () => {
	it( 'renders with correct className', () => {
		const { container } = render( <Loading /> );

		expect( container.firstChild ).toHaveClass(
			'wp-graphql-headless-login__loading'
		);
	} );

	it( 'passes through className prop', () => {
		const { container } = render( <Loading className="custom-class" /> );

		const spinner = container.querySelector( '[data-testid="spinner"]' );
		expect( spinner ).toHaveClass( 'custom-class' );
		expect( spinner ).toHaveClass( 'wp-graphql-headless-login__loading' );
	} );

	it( 'passes through additional props', () => {
		const { container } = render( <Loading style={ { color: 'red' } } /> );

		const spinner = container.querySelector( '[data-testid="spinner"]' );
		expect( spinner ).toHaveStyle( {
			color: 'rgb(255, 0, 0)',
		} );
	} );
} );
