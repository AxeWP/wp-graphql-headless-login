import { Button, Icon, NavigableMenu } from '@wordpress/components';
import { link as LinkSVG } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import { useCurrentScreen } from '@/admin/contexts/screen-context';
import { AllowedScreens } from '@/admin/screens';

import styles from './styles.module.scss';

export const DOCS_URL =
	'https://github.com/AxeWP/wp-graphql-headless-login/blob/main/docs/reference/settings.md';

const LinkIcon = () => {
	return <Icon icon={ LinkSVG } className={ styles.linkIcon } size={ 16 } />;
};

const SCREEN_LABELS: Record< AllowedScreens, string > = {
	providers: __( 'Login Providers', 'wp-graphql-headless-login' ),
	'access-control': __( 'Access Control', 'wp-graphql-headless-login' ),
	'plugin-settings': __( 'Misc', 'wp-graphql-headless-login' ),
};

export const Menu = () => {
	const { currentScreen, setCurrentScreen } = useCurrentScreen();

	return (
		<NavigableMenu orientation="horizontal">
			<ul role="menubar" className={ styles.menu }>
				{
					// Loop through the screen titles and create a button for each one.
					Object.entries( SCREEN_LABELS ).map(
						( [ screen, title ] ) => (
							<li key={ screen }>
								<Button
									key={ screen }
									className={
										currentScreen === screen
											? styles.active
											: ''
									}
									variant="tertiary"
									onClick={ () =>
										setCurrentScreen(
											screen as AllowedScreens
										)
									}
									role="menuitem"
								>
									{ title }
								</Button>
							</li>
						)
					)
				}
				<li>
					<Button
						href={ DOCS_URL }
						variant="tertiary"
						target="_blank"
						rel="noreferrer"
						className="wp-graphql-headless-login__menu-item"
						role="menuitem"
						// Add a link icon to the button.
						icon={ LinkIcon }
						iconPosition="right"
						label={ __( 'Docs', 'wp-graphql-headless-login' ) }
					>
						{ __( 'Docs', 'wp-graphql-headless-login' ) }
					</Button>
				</li>
			</ul>
		</NavigableMenu>
	);
};
