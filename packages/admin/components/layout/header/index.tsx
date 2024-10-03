import { __ } from '@wordpress/i18n';
import { Logo } from '../../logo';
import { AdvancedSettingsToggle } from './advanced-settings-toggle';
import { Menu } from './menu';

import styles from './styles.module.scss';

/**
 * The plugin settings header.
 */
export const Header = () => {
	return (
		<header className={ styles.header }>
			<Logo size={ 90 } />
			<div className={ styles[ 'menu-section' ] }>
				<h1>
					{ __(
						'Headless Login Settings',
						'wp-graphql-headless-login'
					) }
				</h1>
				<Menu />
			</div>
			<div className={ styles[ 'toggle-section' ] }>
				<AdvancedSettingsToggle />
			</div>
		</header>
	);
};
