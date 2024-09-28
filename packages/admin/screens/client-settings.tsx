import { Panel } from '@wordpress/components';
import { ClientSettings } from '@/admin/settings/ClientSettings/ClientSettings';

const ClientSettingsScreen = () => {
	return (
		<Panel className="wp-graphql-headless-login__plugin-settings">
			<ClientSettings />
		</Panel>
	);
};

export default ClientSettingsScreen;
