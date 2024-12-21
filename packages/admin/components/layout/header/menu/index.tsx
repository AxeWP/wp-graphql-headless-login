import {
	Button,
	Icon,
	NavigableMenu,
	Modal,
	Flex,
} from '@wordpress/components';
import { link as LinkSVG } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import { useState } from 'react';
import { useCurrentScreen } from '@/admin/components/screen/context';
import {
	getScreenForSetting,
	getSettingForScreen,
} from '@/admin/components/screen/utils';
import { useSettings } from '@/admin/contexts/settings-context';

import styles from './styles.module.scss';

export const DOCS_URL =
	'https://github.com/AxeWP/wp-graphql-headless-login/blob/main/docs/reference/settings.md';

const LinkIcon = () => {
	return <Icon icon={ LinkSVG } className={ styles.linkIcon } size={ 16 } />;
};

/**
 * Builds and returns the menu object from the wpGraphQLLogin.settings global.
 */
const getMenuObject = (): Record< string, string > => {
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

const MenuItem = ( {
	screen,
	title,
	currentScreen,
	handleMenuClick,
	isDirty,
	isSaving,
}: {
	screen: string;
	title: string;
	currentScreen: string;
	handleMenuClick: ( screen: string ) => void;
	isDirty: boolean;
	isSaving: boolean;
} ) => (
	<li key={ screen } role="menuitem">
		<Button
			key={ screen }
			className={ currentScreen === screen ? styles.active : '' }
			variant="tertiary"
			onClick={ () => handleMenuClick( screen ) }
			disabled={ isSaving }
		>
			{ title }
			{ isDirty && screen === currentScreen && (
				<span
					className={ styles.dirtyIndicator }
					aria-label={ __(
						'Unsaved changes',
						'wp-graphql-headless-login'
					) }
				></span>
			) }
		</Button>
	</li>
);

const SaveChangesModal = ( {
	handleSaveAndContinue,
	handleCancel,
}: {
	handleSaveAndContinue: () => void;
	handleCancel: () => void;
} ) => (
	<Modal
		title={ __( 'Unsaved Changes', 'wp-graphql-headless-login' ) }
		onRequestClose={ handleCancel }
	>
		<p>
			{ __(
				'You have unsaved changes. Do you want to save them before switching screens?',
				'wp-graphql-headless-login'
			) }
		</p>
		<Flex direction="row" justify="flex-end">
			<Button variant="tertiary" onClick={ handleCancel }>
				{ __( 'Cancel', 'wp-graphql-headless-login' ) }
			</Button>
			<Button variant="primary" onClick={ handleSaveAndContinue }>
				{ __( 'Save and continue', 'wp-graphql-headless-login' ) }
			</Button>
		</Flex>
	</Modal>
);

export const Menu = () => {
	const { currentScreen, setCurrentScreen } = useCurrentScreen();
	const { isDirty, isSaving, saveSettings } = useSettings();
	const [ showModal, setShowModal ] = useState( false );
	const [ nextScreen, setNextScreen ] = useState< string | null >( null );

	const handleMenuClick = ( screen: string ) => {
		if ( isDirty ) {
			setNextScreen( screen );
			setShowModal( true );
		} else {
			setCurrentScreen( screen );
		}
	};

	const handleSaveAndContinue = async () => {
		if ( nextScreen ) {
			const settingsKey = getSettingForScreen( currentScreen );
			await saveSettings( settingsKey );

			setCurrentScreen( nextScreen );
			setShowModal( false );
			setNextScreen( null );
		}
	};

	const handleCancel = () => {
		setShowModal( false );
		setNextScreen( null );
	};

	// Build the menu object of screens and labels from the wpGraphQLLogin?.settings.
	const menuItems = getMenuObject();

	return (
		<>
			<NavigableMenu orientation="horizontal">
				<ul role="menubar" className={ styles.menu }>
					{
						// Loop through the screen titles and create a button for each one.
						Object.entries( menuItems ).map(
							( [ screen, title ] ) => (
								<MenuItem
									key={ screen }
									screen={ screen }
									title={ title }
									currentScreen={ currentScreen }
									handleMenuClick={ handleMenuClick }
									isDirty={ isDirty }
									isSaving={ isSaving }
								/>
							)
						)
					}
					<li role="menuitem">
						<Button
							href={ DOCS_URL }
							variant="tertiary"
							target="_blank"
							rel="noreferrer"
							className="wp-graphql-headless-login__menu-item"
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
			{ showModal && (
				<SaveChangesModal
					handleSaveAndContinue={ handleSaveAndContinue }
					handleCancel={ handleCancel }
				/>
			) }
		</>
	);
};
