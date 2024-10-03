import { AppProvider } from './contexts/AppProvider';
import {
	ErrorBoundary,
	Header,
	Screen,
	ScreenProvider,
} from './components/layout';
import { Notices } from './components/notices';

import './admin.scss';

const App = () => {
	return (
		<ErrorBoundary showErrorInfo>
			<AppProvider>
				<ScreenProvider>
					<Header />
					<Screen />
				</ScreenProvider>
				<div className="wp-graphql-headless-login__notices">
					<Notices />
				</div>
			</AppProvider>
		</ErrorBoundary>
	);
};

export default App;
