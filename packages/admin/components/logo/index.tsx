import { ReactComponent as LogoSVG } from '@/admin/assets/logo.svg';
import { SVGIcon } from '@/admin/components/ui';

/**
 * A span element containing the logo SVG.
 */
export const Logo = ( {
	size,
}: {
	size?: number;
} & JSX.IntrinsicElements[ 'svg' ] ) => (
	<>
		<SVGIcon
			icon={ LogoSVG }
			size={ size }
			className="headless-login-logo"
		/>
	</>
);
