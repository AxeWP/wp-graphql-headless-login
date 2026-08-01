import { render, fireEvent } from '@testing-library/react';
import { Logo } from '@/admin/components/logo';

vi.mock( '@/admin/components/ui/svg-icon', () => ( {
	SVGIcon: ( {
		size,
		className,
		onClick,
		icon: _icon,
		// Consumed by the real SVGIcon, so it must not reach the DOM.
		iconName: _iconName,
		...rest
	}: {
		size: number;
		className: string;
		onClick?: () => void;
		icon?: unknown;
		iconName?: string;
		[ key: string ]: unknown;
	} ) => (
		<svg
			data-testid="svg-icon"
			xmlns="http://www.w3.org/2000/svg"
			width={ size }
			height={ size }
			className={ className }
			onClick={ onClick }
			{ ...rest }
		/>
	),
} ) );

describe( 'Logo Component', () => {
	describe( 'Renders logo SVG', () => {
		it( 'renders SVG element', () => {
			const { container } = render( <Logo size={ 90 } /> );

			const svg = container.querySelector( 'svg' );
			expect( svg ).toBeInTheDocument();
		} );

		it( 'renders SVG with correct namespace', () => {
			const { container } = render( <Logo size={ 90 } /> );

			const svg = container.querySelector( 'svg' );
			expect( svg ).toHaveAttribute(
				'xmlns',
				'http://www.w3.org/2000/svg'
			);
		} );

		it( 'renders with correct size', () => {
			const { container } = render( <Logo size={ 90 } /> );

			const svg = container.querySelector( 'svg' );
			expect( svg ).toHaveAttribute( 'height', '90' );
			expect( svg ).toHaveAttribute( 'width', '90' );
		} );
	} );

	describe( 'Has correct alt/title attributes', () => {
		it( 'passes through title attribute', () => {
			const { container } = render(
				// @ts-expect-error - title is part of SVGProps but TypeScript type inference has limitations
				<Logo size={ 50 } title="Headless Login Logo" />
			);

			const svg = container.querySelector( 'svg' );
			expect( svg ).toHaveAttribute( 'title', 'Headless Login Logo' );
		} );

		it( 'passes through aria-label attribute (alt equivalent for SVG)', () => {
			const { container } = render(
				<Logo
					size={ 50 }
					aria-label="Headless Login for WPGraphQL logo"
				/>
			);

			const svg = container.querySelector( 'svg' );
			expect( svg ).toHaveAttribute(
				'aria-label',
				'Headless Login for WPGraphQL logo'
			);
		} );

		it( 'passes through role attribute for accessibility', () => {
			const { container } = render( <Logo size={ 50 } role="img" /> );

			const svg = container.querySelector( 'svg' );
			expect( svg ).toHaveAttribute( 'role', 'img' );
		} );
	} );

	describe( 'Has correct styling', () => {
		it( 'applies default logo className', () => {
			const { container } = render( <Logo size={ 32 } /> );

			const svg = container.querySelector( 'svg' );
			expect( svg ).toHaveClass( 'headless-login-logo' );
		} );

		it( 'applies custom className in addition to default', () => {
			const { container } = render(
				<Logo size={ 32 } className="custom-logo" />
			);

			const svg = container.querySelector( 'svg' );
			expect( svg ).toHaveClass( 'custom-logo' );
			expect( svg ).toHaveClass( 'headless-login-logo' );
		} );

		it( 'passes through additional styling props', () => {
			const { container } = render(
				<Logo size={ 50 } style={ { color: 'red' } } />
			);

			const svg = container.querySelector( 'svg' );
			expect( svg ).toHaveStyle( {
				color: 'rgb(255, 0, 0)',
			} );
		} );
	} );

	describe( 'Interaction', () => {
		it( 'passes through click handler', () => {
			const handleClick = vi.fn();
			const { container } = render(
				<Logo size={ 50 } onClick={ handleClick } />
			);

			const svg = container.querySelector( 'svg' );
			if ( ! svg ) {
				throw new Error( 'SVG element not found' );
			}
			fireEvent.click( svg );
			expect( handleClick ).toHaveBeenCalledTimes( 1 );
			expect( container.querySelector( 'svg' ) ).toHaveAttribute(
				'height',
				'50'
			);
		} );
	} );

	describe( 'Props passthrough', () => {
		it( 'passes through additional props', () => {
			const { container } = render(
				<Logo size={ 50 } data-custom="test-value" />
			);

			const svg = container.querySelector( 'svg' );
			expect( svg ).toHaveAttribute( 'data-custom', 'test-value' );
		} );
	} );
} );
