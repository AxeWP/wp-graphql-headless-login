/* eslint-disable @wordpress/no-unsafe-wp-apis */
import { useState } from '@wordpress/element';
import {
	Flex,
	FlexItem,
	FlexBlock,
	Icon,
	Panel,
	Placeholder,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { AppProvider } from './contexts/AppProvider';
/**
 * Internal dependencies.
 */
import type { wpGraphQLLogin } from '.';
import { ReactComponent as Logo } from './assets/logo.svg';
import { Header, Notices, ClientMenu } from './components';
import { AccessControlSettings, PluginSettings } from './settings';
import { ClientSettings } from './settings/ClientSettings/ClientSettings';
import './admin.scss';

function App() {
	const [activeClient, setActiveClient] = useState(
		Object.keys(wpGraphQLLogin?.settings.providers)?.[0] || null
	);

	return (
		<AppProvider>
			<Header />
			<Flex
				className="wp-graphql-headless-login__main"
				align="flex-start"
			>
				<FlexItem className="wp-graphql-headless-login__sidebar">
					<ClientMenu
						activeClient={activeClient}
						setActiveClient={setActiveClient}
					/>
				</FlexItem>
				<FlexBlock>
					<Panel className="wp-graphql-headless-login__client">
						{activeClient ? (
							<ClientSettings
								key={`client-${activeClient}`}
								clientSlug={activeClient}
							/>
						) : (
							<Placeholder
								icon={<Icon icon={<Logo />} />}
								title={__(
									'No clients found',
									'wp-graphql-headless-login'
								)}
								instructions={__(
									'@todo: add instructions. This should be some nice onboardy stuff with links to docs etc.',
									'wp-graphql-headless-login'
								)}
							/>
						)}
					</Panel>
				</FlexBlock>
			</Flex>

			<Panel className="wp-graphql-headless-login__plugin-settings">
				<PluginSettings />
			</Panel>
			<Panel className="wp-graphql-headless-login__ac-settings">
				<AccessControlSettings />
			</Panel>

			<div className="wp-graphql-headless-login__notices">
				<Notices />
			</div>
		</AppProvider>
	);
}

export default App;
