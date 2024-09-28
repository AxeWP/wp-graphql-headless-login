import { lazy, Suspense } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useCurrentScreen } from '@/admin/contexts/screen-context';
import { Loading } from '@/admin/components/ui';

const AccessControlScreen = lazy( () => import( './access-control' ) );
const ClientSettingsScreen = lazy( () => import( './client-settings' ) );
const PluginSettingsScreen = lazy( () => import( './plugin-settings' ) );

export type AllowedScreens = 'access-control' | 'providers' | 'plugin-settings';

/**
 * The screens that are available in the admin, along with their associated React components.
 */
export const SCREEN_COMPONENTS: Record<
	AllowedScreens,
	React.LazyExoticComponent< () => JSX.Element >
> = {
	providers: ClientSettingsScreen,
	'access-control': AccessControlScreen,
	'plugin-settings': PluginSettingsScreen,
};

/**
 * The titles of the screens that are available in the admin.
 */
export const SCREEN_TITLES: Record< AllowedScreens, string > = {
	providers: __( 'Login Providers', 'wp-graphql-headless-login' ),
	'access-control': __( 'Access Control', 'wp-graphql-headless-login' ),
	'plugin-settings': __( 'Plugin Settings', 'wp-graphql-headless-login' ),
};

export const Screen = () => {
	const { currentScreen } = useCurrentScreen();
	const ScreenComponent =
		SCREEN_COMPONENTS[ currentScreen ] || SCREEN_COMPONENTS.providers;

	return (
		<Suspense fallback={ <Loading /> }>
			<ScreenComponent />
		</Suspense>
	);
};
