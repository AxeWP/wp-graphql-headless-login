import { __ } from '@wordpress/i18n';
import { Panel, PanelBody, PanelRow } from '@wordpress/components';
import clsx from 'clsx';
import { lazy, Suspense, type PropsWithChildren } from 'react';
import { Loading } from '@/admin/components/ui';
import { useCurrentScreen } from './context';
import { SettingsScreen } from './setting-screen';
import type { AllowedSettingKeys } from '@/admin/types';

import styles from './styles.module.scss';

const ClientSettingsScreen = lazy(
	() => import( '../provider-config/ClientSettings' )
);

export type AllowedScreens = 'access-control' | 'providers' | 'plugin-settings';

export const SCREEN_MAP: Record< AllowedScreens, AllowedSettingKeys > = {
	'access-control': 'wpgraphql_login_access_control',
	'plugin-settings': 'wpgraphql_login_settings',
	providers: 'providers',
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

	const title = SCREEN_TITLES[ currentScreen ];
	const description = SCREEN_DESCRIPTIONS[ currentScreen ];

	return (
		<Suspense fallback={ <Loading /> }>
			<Wrapper title={ title } description={ description }>
				{ currentScreen === 'providers' ? (
					<ClientSettingsScreen />
				) : (
					<SettingsScreen
						settingKey={ SCREEN_MAP[ currentScreen ] }
					/>
				) }
			</Wrapper>
		</Suspense>
	);
};
