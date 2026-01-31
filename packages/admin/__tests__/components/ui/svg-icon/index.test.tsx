import { render, fireEvent } from '@testing-library/react';
import { SVGIcon } from '@/admin/components/ui/svg-icon';

vi.mock( '@/admin/components/ui/svg-icon/styles.module.scss', () => ( {
	default: {
		'svg-icon': 'mock-svg-icon',
	},
} ) );

describe( 'SVG Icon Component', () => {
	const MockIcon = ( props: React.SVGProps< SVGSVGElement > ) => (
		<svg data-test="mock-icon" { ...props } />
	);

	describe( 'Renders SVG from icon prop', () => {
		it( 'renders the icon component', () => {
			const { container } = render(
				<SVGIcon icon={ MockIcon } name="test-icon" />
			);

			const svg = container.querySelector( 'svg' );
			expect( svg ).toBeInTheDocument();
			expect( svg ).toHaveAttribute( 'data-test', 'mock-icon' );
		} );
	} );

	describe( 'Passes size prop correctly', () => {
		it( 'renders with correct size', () => {
			const { container } = render(
				<SVGIcon icon={ MockIcon } name="test-icon" size={ 32 } />
			);

			const svg = container.querySelector( 'svg' );

			expect( svg ).toHaveAttribute( 'width', '32' );
			expect( svg ).toHaveAttribute( 'height', '32' );
		} );

		it( 'renders with default size', () => {
			const { container } = render(
				<SVGIcon icon={ MockIcon } name="test-icon" />
			);

			const svg = container.querySelector( 'svg' );

			expect( svg ).toHaveAttribute( 'width', '24' );
			expect( svg ).toHaveAttribute( 'height', '24' );
		} );
	} );

	describe( 'Passes className correctly', () => {
		it( 'passes through custom classNames', () => {
			const { container } = render(
				<SVGIcon
					icon={ MockIcon }
					name="test-icon"
					classNames="custom-class"
				/>
			);

			const svg = container.querySelector( 'svg' );
			expect( svg ).toHaveClass( 'custom-class' );
			expect( svg ).toHaveClass( 'mock-svg-icon' );
		} );

		it( 'passes through name prop', () => {
			const { container } = render(
				<SVGIcon icon={ MockIcon } iconName="test-icon" />
			);

			const svg = container.querySelector( 'svg' );
			expect( svg ).toHaveClass( 'svg-icon-test-icon' );
		} );
	} );

	describe( 'Passes other HTML attributes', () => {
		it( 'passes through data attributes', () => {
			const { container } = render(
				<SVGIcon
					icon={ MockIcon }
					name="test-icon"
					data-custom="test-value"
				/>
			);

			const svg = container.querySelector( 'svg' );
			expect( svg ).toHaveAttribute( 'data-custom', 'test-value' );
		} );

		it( 'passes through aria-label for accessibility', () => {
			const { container } = render(
				<SVGIcon
					icon={ MockIcon }
					name="test-icon"
					aria-label="Test Icon"
				/>
			);

			const svg = container.querySelector( 'svg' );
			expect( svg ).toHaveAttribute( 'aria-label', 'Test Icon' );
		} );

		it( 'passes through role attribute', () => {
			const { container } = render(
				<SVGIcon icon={ MockIcon } name="test-icon" role="img" />
			);

			const svg = container.querySelector( 'svg' );
			expect( svg ).toHaveAttribute( 'role', 'img' );
		} );

		it( 'passes through style prop', () => {
			const { container } = render(
				<SVGIcon
					icon={ MockIcon }
					name="test-icon"
					style={ { color: 'red', fill: 'blue' } }
				/>
			);

			const svg = container.querySelector( 'svg' );
			expect( svg ).toHaveStyle( {
				color: 'rgb(255, 0, 0)',
				fill: 'blue',
			} );
		} );

		it( 'passes through onClick handler', () => {
			const handleClick = vi.fn();
			const { container } = render(
				<SVGIcon
					icon={ MockIcon }
					name="test-icon"
					onClick={ handleClick }
				/>
			);

			const svg = container.querySelector( 'svg' );
			if ( svg ) {
				fireEvent.click( svg );
				expect( handleClick ).toHaveBeenCalledTimes( 1 );
			}
		} );

		it( 'passes through viewBox attribute', () => {
			const { container } = render(
				<SVGIcon
					icon={ MockIcon }
					name="test-icon"
					viewBox="0 0 24 24"
				/>
			);

			const svg = container.querySelector( 'svg' );
			expect( svg ).toHaveAttribute( 'viewBox', '0 0 24 24' );
		} );
	} );
} );
