import domReady from '@wordpress/dom-ready';
import { createRoot } from '@wordpress/element';
import { createHooks } from '@wordpress/hooks';
import { StrictMode } from 'react';

import App from './app';
import { ErrorBoundary } from './layout/error-boundry/error-boundry';

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
			<ErrorBoundary showErrorInfo>
				<App />
			</ErrorBoundary>
		</StrictMode>
	);
} );

wpGraphQLLogin.hooks = hooks;
