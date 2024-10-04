import {
	ErrorBoundary,
	Header,
	Screen,
	ScreenProvider,
} from './components/layout';
import { Notices } from './components/notices';
import { SettingsProvider } from './contexts/settings-context';

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
