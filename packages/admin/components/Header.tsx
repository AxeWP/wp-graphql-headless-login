import { Icon, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { ReactComponent as Logo } from '../assets/logo.svg';
import { useAppContext } from '../contexts/AppProvider';

function Header(): JSX.Element {
	const { showAdvancedSettings, setShowAdvancedSettings } = useAppContext();

	return (
		<div className="wp-graphql-headless-login__header">
			<h1 className="wp-graphql-headless-login__title">
				<Icon icon={ <Logo /> } />
				{ __(
					'Headless Login Settings',
					'wp-graphql-headless-login'
				) }{ ' ' }
			</h1>
			{ showAdvancedSettings !== undefined && (
				<ToggleControl
					className="wp-graphql-headless-login__advanced-settings-toggle"
					label={ __(
						'Show advanced settings',
						'wp-graphql-headless-login'
					) }
					checked={ showAdvancedSettings }
					onChange={ ( value ) => setShowAdvancedSettings( value ) }
				/>
			) }
			{ /* Add button link to documentation */ }
			<div className="wp-graphql-headless-login__documentation-link">
				<a
					href="https://github.com/AxeWP/wp-graphql-headless-login-beta/blob/main/docs/settings.md"
					target="_blank"
					rel="noreferrer"
				>
					{ __( 'Documentation (WIP)', 'wp-graphql-headless-login' ) }
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
