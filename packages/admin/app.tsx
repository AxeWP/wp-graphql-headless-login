import { ErrorBoundary } from '@/admin/components/layout/error-boundary';
import { Header } from '@/admin/components/layout/header';
import { Notices } from '@/admin/components/notices';
import { Screen } from '@/admin/components/screen/screen';
import { ScreenProvider } from '@/admin/components/screen/context';
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
