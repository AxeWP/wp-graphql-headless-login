/**
 * External dependencies.
 */
import { render } from '@wordpress/element';
import { createHooks } from '@wordpress/hooks';

/**
 * Internal dependencies.
 */
import SettingsScreen from './SettingsScreen';

export const hooks = createHooks();

declare const wpGraphQLLogin: {
	hooks: typeof hooks;
	settings: Record<string, any>;
};

// Render the app.
document.addEventListener('DOMContentLoaded', () => {
	const htmlOutput = document.getElementById('wpgraphql_login_settings');

	if (htmlOutput) {
		render(<SettingsScreen />, htmlOutput);
	}
});

wpGraphQLLogin.hooks = hooks;
