import { Panel } from '@wordpress/components';
import { AccessControlSettings } from '@/admin/settings';

const AccessControlScreen = () => {
	return (
		<Panel className="wp-graphql-headless-login__ac-settings">
			<AccessControlSettings />
		</Panel>
	);
};

export default AccessControlScreen;
