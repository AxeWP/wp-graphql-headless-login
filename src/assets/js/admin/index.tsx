/**
 * External dependencies.
 */
import { render } from '@wordpress/element';
import { createHooks } from '@wordpress/hooks';

/**
 * Internal dependencies.
 */
import App from './app';

export const hooks = createHooks();

export declare const wpGraphQLLogin: {
	hooks: typeof hooks;
	settings: {
		accessControl: Record<string, any>;
		providers: Record<string, any>;
		plugin: Record<string, any>;
	};
	nonce: string;
	secret: {
		hasKey: bool;
		isConstant: bool;
	};
};

// Render the app.
document.addEventListener('DOMContentLoaded', () => {
	const htmlOutput = document.getElementById('wpgraphql_login_settings');

	if (htmlOutput) {
		render(<App />, htmlOutput);
	}
});

wpGraphQLLogin.hooks = hooks;
