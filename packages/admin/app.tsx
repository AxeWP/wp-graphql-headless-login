import { AppProvider } from './contexts/AppProvider';
import { Notices } from './components/notices';
import { Header, Screen, ScreenProvider } from './layout';

import './admin.scss';

const App = () => {
	return (
		<AppProvider>
			<ScreenProvider>
				<Header />
				<Screen />
			</ScreenProvider>
			<div className="wp-graphql-headless-login__notices">
				<Notices />
			</div>
		</AppProvider>
	);
};

export default App;
