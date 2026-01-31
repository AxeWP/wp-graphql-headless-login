import { ReactComponent as LogoSVG } from '@/admin/assets/logo.svg';
import { SVGIcon } from '@/admin/components/ui/svg-icon';
import type { SVGProps } from 'react';

/**
 * A span element containing the logo SVG.
 */
export const Logo = ( {
	size,
	className,
	onClick,
	...rest
}: {
	size: number;
	className?: string;
	onClick?: () => void;
} & Omit<
	SVGProps< SVGSVGElement >,
	'width' | 'height' | 'className' | 'onClick' | 'ref'
> ) => (
	<SVGIcon
		{ ...rest }
		icon={ LogoSVG }
		iconName="headless-login-logo"
		size={ size }
		className={
			className
				? `headless-login-logo ${ className }`
				: 'headless-login-logo'
		}
		onClick={ onClick }
	/>
);
