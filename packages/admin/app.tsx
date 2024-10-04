import { ErrorBoundary, Header } from '@/admin/components/layout';
import { Screen, ScreenProvider } from '@/admin/components/screen';
import { Notices } from '@/admin/components/notices';
import { SettingsProvider } from '@/admin/contexts/settings-context';

import './admin.scss';

const App = () => {
	return (
		<ErrorBoundary showErrorInfo>
			<SettingsProvider>
				<ScreenProvider>
					<Header />
					<Screen />
				</ScreenProvider>
				<div className="wp-graphql-headless-login__notices">
					<Notices />
				</div>
			</SettingsProvider>
		</ErrorBoundary>
	);
};

export default App;
