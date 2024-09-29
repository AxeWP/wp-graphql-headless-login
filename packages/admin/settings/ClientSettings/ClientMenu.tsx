/* eslint-disable @wordpress/no-unsafe-wp-apis */
import {
	__experimentalNavigation as Navigation,
	__experimentalNavigationItem as NavigationItem,
	__experimentalNavigationMenu as NavigationMenu,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useEntityProp } from '@wordpress/core-data';
import { useClientContext } from '../../contexts/ClientProvider';

import styles from './styles.module.scss';

export function StatusBadge( { provider }: { provider: string } ) {
	const [ providerConfig ] = useEntityProp( 'root', 'site', provider );

	const isEnabled = providerConfig?.isEnabled ?? false;

	const title = isEnabled
		? __( 'Enabled', 'wp-graphql-headless-login' )
		: __( 'Disabled', 'wp-graphql-headless-login' );

	return (
		<div className={ styles[ 'status-badge' ] }>
			<span
				className={ isEnabled ? styles?.enabled : undefined }
				aria-label={ title }
				title={ title }
			></span>
		</div>
	);
}

export function ClientMenu() {
	const providers = Object.keys( wpGraphQLLogin?.settings?.providers || {} );
	const { activeClient, setActiveClient } = useClientContext();

	return (
		<Navigation activeItem={ activeClient }>
			<NavigationMenu
				title={ __( 'Providers', 'wp-graphql-headless-login' ) }
			>
				{ providers.length > 0 &&
					providers.map( ( provider ) => (
						<NavigationItem
							className={ styles.menuItem }
							key={ provider }
							item={ provider }
							title={
								wpGraphQLLogin?.settings?.providers?.[
									provider
								]?.name?.default as string
							}
							icon={ <StatusBadge provider={ provider } /> }
							onClick={ () => setActiveClient( provider ) }
						/>
					) ) }
			</NavigationMenu>
		</Navigation>
	);
}
