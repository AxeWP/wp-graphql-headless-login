import { Flex, FlexItem, FlexBlock, Panel } from '@wordpress/components';
import { ClientPanel } from './ClientPanel';
import { ClientMenu } from './ClientMenu';
import { ClientProvider } from '../../contexts/ClientProvider';

import styles from './styles.module.scss';

function ClientSettings() {
	return (
		<Flex align="flex-start">
			<ClientProvider>
				<FlexItem className={ styles.sidebar }>
					<ClientMenu />
				</FlexItem>
				<FlexBlock>
					<Panel className="wp-graphql-headless-login__client">
						<ClientPanel />
					</Panel>
				</FlexBlock>
			</ClientProvider>
		</Flex>
	);
}

export default ClientSettings;
