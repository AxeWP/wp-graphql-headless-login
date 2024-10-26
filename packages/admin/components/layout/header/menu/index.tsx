import { Button, Icon, NavigableMenu } from '@wordpress/components';
import { link as LinkSVG } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import { useCurrentScreen } from '@/admin/components/screen/context';
import { getScreenForSetting } from '@/admin/components/screen/utils';

import styles from './styles.module.scss';

export const DOCS_URL =
	'https://github.com/AxeWP/wp-graphql-headless-login/blob/main/docs/reference/settings.md';

const LinkIcon = () => {
	return <Icon icon={ LinkSVG } className={ styles.linkIcon } size={ 16 } />;
};

/**
 * Builds and returns the menu object from the wpGraphQLLogin.settings global.
 */
export const getMenuObject = (): Record< string, string > => {
	const settings = wpGraphQLLogin.settings;

	const menu: Record< string, string > = {
		providers: '', // We want this as the first key.
	};

	for ( const key in settings ) {
		// @todo get providers from global after the refactor.
		const menuTitle =
			settings[ key ].label ||
			__( 'Providers', 'wp-graphql-headless-login' );
		const screen = getScreenForSetting( key );

		menu[ screen ] = menuTitle;
	}

	return menu;
};

export const Menu = () => {
	const { currentScreen, setCurrentScreen } = useCurrentScreen();

	// Build the menu object of screens and labels from the wpGraphQLLogin?.settings.
	const menuItems = getMenuObject();

	return (
		<NavigableMenu orientation="horizontal">
			<ul role="menubar" className={ styles.menu }>
				{
					// Loop through the screen titles and create a button for each one.
					Object.entries( menuItems ).map( ( [ screen, title ] ) => (
						<li key={ screen }>
							<Button
								key={ screen }
								className={
									currentScreen === screen
										? styles.active
										: ''
								}
								variant="tertiary"
								onClick={ () => setCurrentScreen( screen ) }
								role="menuitem"
							>
								{ title }
							</Button>
						</li>
					) )
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
