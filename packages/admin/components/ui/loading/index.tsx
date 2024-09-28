import { Spinner } from '@wordpress/components';
import clsx from 'clsx';

export const Loading = ( {
	className,
	...rest
}: React.ComponentProps< typeof Spinner > ) => {
	const classes = clsx( 'wp-graphql-headless-login__loading', className );

	return <Spinner className={ classes } { ...rest } />;
};
