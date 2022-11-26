/* eslint-disable @wordpress/no-unsafe-wp-apis */
/**
 * External dependencies.
 */
import { Fragment, useState } from '@wordpress/element';
import {
	Flex,
	FlexItem,
	FlexBlock,
	Icon,
	Panel,
	SnackbarList,
	ToggleControl,
	Placeholder,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { store as coreStore, useEntityProp } from '@wordpress/core-data';
import { useDispatch, useSelect } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';

/**
 * Internal dependencies.
 */
import { ClientSettings } from './components/ClientSettings';
import { default as ClientMenu } from './components/ClientMenu';
import './admin.scss';
import { ReactComponent as Logo } from '../../../../assets/logo.svg';
import { PluginOptions } from './components/PluginOptions';

declare const wpGraphQLLogin: {
	hooks: typeof hooks;
	settings: Record<string, any>;
};

const Notices = () => {
	const notices = useSelect(
		(select) =>
			select(noticesStore)
				.getNotices()
				.filter((notice) => notice.type === 'snackbar'),
		[]
	);
	const { removeNotice } = useDispatch(noticesStore);
	return (
		<SnackbarList
			className="edit-site-notices"
			notices={notices}
			onRemove={removeNotice}
		/>
	);
};

function SettingsScreen() {
	const [showAdvancedSettings, setShowAdvancedSettings] = useEntityProp(
		'root',
		'site',
		'wpgraphql_login_settings_show_advanced_settings'
	);

	const { saveEditedEntityRecord } = useDispatch(coreStore);
	const [activeClient, setActiveClient] = useState(
		Object.keys(wpGraphQLLogin?.settings)?.[0] || null
	);

	return (
		<Fragment>
			<div className="wp-graphql-headless-login__header">
				<h1 className="wp-graphql-headless-login__title">
					<Icon icon={<Logo />} />
					{__(
						'Headless Login Settings',
						'wp-graphql-headless-login'
					)}{' '}
				</h1>
				{showAdvancedSettings !== undefined && (
					<ToggleControl
						className="wp-graphql-headless-login__advanced-settings-toggle"
						label={__(
							'Show advanced settings',
							'wp-graphql-headless-login'
						)}
						checked={showAdvancedSettings}
						onChange={(value: boolean) => {
							setShowAdvancedSettings(value);
							saveEditedEntityRecord('root', 'site', undefined, {
								wpgraphql_login_settings_show_advanced_settings:
									value,
							});
						}}
					/>
				)}
				{/* Add button link to documentation */}
				<div className="wp-graphql-headless-login__documentation-link">
					<a
						href="https://github.com/AxeWP/wp-graphql-headless-login-beta/blob/main/docs/settings.md"
						target="_blank"
						rel="noreferrer"
					>
						{__('Documentation (WIP).', 'wp-graphql-headless-login')}
						<Icon
							icon="external"
							className="wp-graphql-headless-login__documentation-link-icon"
						/>
					</a>
				</div>
			</div>
			<Flex
				className="wp-graphql-headless-login__main"
				align="flex-start"
			>
				<FlexItem className="wp-graphql-headless-login__sidebar">
					<ClientMenu
						activeClient={activeClient}
						setActiveClient={setActiveClient}
					/>
				</FlexItem>
				<FlexBlock>
					<Panel className="wp-graphql-headless-login__client">
						{activeClient ? (
							<ClientSettings
								key={`client-${activeClient}`}
								clientSlug={activeClient}
							/>
						) : (
							<Placeholder
								icon={<Icon icon={<Logo />} />}
								title={__(
									'No clients found',
									'wp-graphql-headless-login'
								)}
								instructions={__(
									'@todo: add instructions. This should be some nice onboardy stuff with links to docs etc.',
									'wp-graphql-headless-login'
								)}
							/>
						)}
					</Panel>
				</FlexBlock>
			</Flex>
			<Panel className="wp-graphql-headless-login__plugin-settings">
				<PluginOptions />
			</Panel>

			<div className="wp-graphql-headless-login__notices">
				<Notices />
			</div>
		</Fragment>
	);
}

export default SettingsScreen;
