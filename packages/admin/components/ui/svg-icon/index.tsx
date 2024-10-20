import clsx from 'clsx';
import { forwardRef, SVGProps, memo, FunctionComponent } from 'react';

import styles from './styles.module.scss';

interface Props {
	icon: FunctionComponent< SVGProps< SVGSVGElement > >;
	name?: string;
	classNames?: string;
	size?: number;
}

const SVGIconComponent = forwardRef<
	SVGSVGElement,
	Props & SVGProps< SVGSVGElement >
>( ( props, ref ) => {
	const {
		size = 24,
		name = 'headless-login-logo',
		onClick,
		classNames,
		icon: Icon,
		...otherProps
	} = props;
	const iconName = `svg-icon-${ name }`;
	const iconClass = clsx( styles[ 'svg-icon' ], iconName, classNames );

	return (
		<Icon
			xmlns="http://www.w3.org/2000/svg"
			className={ iconClass }
			height={ size }
			width={ size }
			onClick={ onClick }
			ref={ ref }
			{ ...otherProps }
		/>
	);
} );

export const SVGIcon = memo( SVGIconComponent );
