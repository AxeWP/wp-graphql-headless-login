import { __ } from '@wordpress/i18n';
import { Panel, PanelBody, PanelRow } from '@wordpress/components';
import { lazy, Suspense, type PropsWithChildren } from 'react';
import { useCurrentScreen } from '@/admin/contexts/screen-context';
import { Loading } from '@/admin/components/ui';

import styles from './styles.module.scss';
import clsx from 'clsx';

const AccessControlScreen = lazy(
	() => import( '@/admin/settings/AccessControlSettings' )
);
const ClientSettingsScreen = lazy(
	() => import( '../settings/ClientSettings/ClientSettings' )
);
const PluginSettingsScreen = lazy(
	() => import( '../settings/PluginSettings/PluginSettings' )
);

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
 *
 * @todo get from the server.
 */
const SCREEN_TITLES: Record< AllowedScreens, string > = {
	'access-control': __(
		'Access Control Settings',
		'wp-graphql-headless-login'
	),
	'plugin-settings': __( 'Plugin Settings', 'wp-graphql-headless-login' ),
	providers: __( 'Login Providers', 'wp-graphql-headless-login' ),
};

/**
 * The descriptions of the screens that are available in the admin.
 *
 * @todo get from the server.
 */
const SCREEN_DESCRIPTIONS: Record< AllowedScreens, string > = {
	'access-control': __(
		'Configure the Access Control headers for the plugin.',
		'wp-graphql-headless-login'
	),
	'plugin-settings': __(
		'Configure the plugin settings.',
		'wp-graphql-headless-login'
	),
	providers: __(
		'Configure the Authentication Providers that are available to users.',
		'wp-graphql-headless-login'
	),
};

const Wrapper = ( {
	title,
	children,
	className,
	description,
}: PropsWithChildren< {
	title: string;
	description?: string;
	className?: string;
} > ) => {
	const classes = clsx( styles.wrapper, className );

	return (
		<Panel className={ classes }>
			<PanelBody>
				<PanelRow>
					<h2 className="components-panel__body-title">{ title }</h2>
				</PanelRow>
				{ description && (
					<div dangerouslySetInnerHTML={ { __html: description } } />
				) }
			</PanelBody>
			{ children }
		</Panel>
	);
};

export const Screen = () => {
	const { currentScreen } = useCurrentScreen();

	const ScreenComponent = SCREEN_COMPONENTS[ currentScreen ];
	const title = SCREEN_TITLES[ currentScreen ];
	const description = SCREEN_DESCRIPTIONS[ currentScreen ];

	return (
		<Suspense fallback={ <Loading /> }>
			<Wrapper title={ title } description={ description }>
				<ScreenComponent />
			</Wrapper>
		</Suspense>
	);
};
