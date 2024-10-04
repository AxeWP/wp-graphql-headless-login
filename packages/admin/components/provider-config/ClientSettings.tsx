import { Flex, FlexItem, FlexBlock, Panel } from '@wordpress/components';
import { ClientPanel } from './ClientPanel';
import { ClientMenu } from './ClientMenu';
import { ProviderConfigProvider } from '@/admin/contexts/provider-config-context';

import styles from './styles.module.scss';

function ClientSettings() {
	return (
		<Flex align="flex-start">
			<ProviderConfigProvider>
				<FlexItem className={ styles.sidebar }>
					<ClientMenu />
				</FlexItem>
				<FlexBlock>
					<Panel className="wp-graphql-headless-login__client">
						<ClientPanel />
					</Panel>
				</FlexBlock>
			</ProviderConfigProvider>
		</Flex>
	);
}

export default ClientSettings;
