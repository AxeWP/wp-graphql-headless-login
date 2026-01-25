import domReady from '@wordpress/dom-ready';
import { createHooks } from '@wordpress/hooks';
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';

import App from './app';

export const hooks = createHooks();

// Render the app.
domReady( () => {
	const container = document.getElementById( 'wpgraphql_login_settings' );

	if ( ! container ) {
		return;
	}

	const root = createRoot( container );

	root.render(
		<StrictMode>
			<App />
		</StrictMode>
	);
} );

wpGraphQLLogin.hooks = hooks;
