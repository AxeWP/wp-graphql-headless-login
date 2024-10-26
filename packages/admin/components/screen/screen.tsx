import { Panel, PanelBody, PanelRow } from '@wordpress/components';
import clsx from 'clsx';
import { lazy, Suspense, type PropsWithChildren } from 'react';
import { Loading } from '@/admin/components/ui/loading';
import { useCurrentScreen } from './context';
import { SettingsScreen } from './setting-screen';

import styles from './styles.module.scss';
import { getSettingForScreen } from './utils';
import { __ } from '@wordpress/i18n';

const ClientSettingsScreen = lazy(
	() => import( '../provider-config/ClientSettings' )
);

/**
 * The titles of the screens that are available in the admin.
 *
 * @todo get from the server.
 */

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

	const settingKey = getSettingForScreen( currentScreen );

	// @todo get provider context from global.
	const title =
		wpGraphQLLogin?.settings[ settingKey ]?.title ||
		__( 'Login Providers', 'wp-graphql-headless-login' );
	const description =
		wpGraphQLLogin?.settings[ settingKey ]?.description ||
		__(
			'Configure the Authentication Providers that are available to users.',
			'wp-graphql-headless-login'
		);

	return (
		<Suspense fallback={ <Loading /> }>
			<Wrapper title={ title } description={ description }>
				{ currentScreen === 'providers' ? (
					<ClientSettingsScreen />
				) : (
					<SettingsScreen settingKey={ settingKey } />
				) }
			</Wrapper>
		</Suspense>
	);
};
