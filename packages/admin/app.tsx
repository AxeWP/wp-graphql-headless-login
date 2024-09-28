import { AppProvider } from './contexts/AppProvider';
import { Notices } from './components/notices';
import './admin.scss';
import { Header } from './layout';
import { ScreenProvider } from './contexts/screen-context';
import { Screen } from './screens';

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
