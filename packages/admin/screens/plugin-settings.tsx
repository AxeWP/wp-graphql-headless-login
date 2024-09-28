import { Panel } from '@wordpress/components';
import { PluginSettings } from '@/admin/settings';

const PluginSettingsScreen = () => {
	return (
		<Panel className="wp-graphql-headless-login__plugin-settings">
			<PluginSettings />
		</Panel>
	);
};

export default PluginSettingsScreen;
