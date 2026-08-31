import { Button, Flex, FlexItem } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useEntityProp } from '@wordpress/core-data';

import { useClientContext } from '@/admin/contexts/provider-config-context';

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
				className={ isEnabled ? styles?.[ 'enabled' ] : undefined }
				aria-label={ title }
				title={ title }
			/>
		</div>
	);
}

export function ClientMenu() {
	const providers = Object.keys( wpGraphQLLogin?.settings?.providers || {} );

	const { activeClient, setActiveClient } = useClientContext();

	return (
		<Flex direction="column" gap={ 1 }>
			<FlexItem>
				<strong>
					{ __( 'Providers', 'wp-graphql-headless-login' ) }
				</strong>
			</FlexItem>

			{ providers.map( ( provider ) => (
				<FlexItem key={ provider }>
					<Button
						className={ styles?.[ 'menuItem' ] ?? '' }
						variant={
							activeClient === provider ? 'secondary' : 'tertiary'
						}
						onClick={ () => setActiveClient( provider ) }
						aria-current={
							activeClient === provider ? 'page' : undefined
						}
					>
						<StatusBadge provider={ provider } />

						{
							wpGraphQLLogin?.settings?.providers?.[ provider ]?.[
								'name'
							]?.default as string
						}
					</Button>
				</FlexItem>
			) ) }
		</Flex>
	);
}
