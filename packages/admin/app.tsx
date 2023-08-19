import { Panel } from '@wordpress/components';
import { AppProvider } from './contexts/AppProvider';
import { Header, Notices } from './components';
import { AccessControlSettings, PluginSettings } from './settings';
import './admin.scss';
import { ClientSettings } from './settings/ClientSettings/ClientSettings';

function App() {
	return (
		<AppProvider>
			<Header />

			<ClientSettings />

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
