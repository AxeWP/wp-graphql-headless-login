import { Icon, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { ReactComponent as Logo } from '../assets/logo.svg';
import { store } from '@wordpress/core-data';
import { useDispatch } from '@wordpress/data';

function Header({
	showAdvancedSettings,
	setShowAdvancedSettings,
}): JSX.Element {
	const { saveEditedEntityRecord } = useDispatch(store);

	return (
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
					{__('Documentation (WIP)', 'wp-graphql-headless-login')}
					<Icon
						icon="external"
						className="wp-graphql-headless-login__documentation-link-icon"
					/>
				</a>
			</div>
		</div>
	);
}

export default Header;
