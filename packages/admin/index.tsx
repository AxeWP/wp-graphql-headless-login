import domReady from '@wordpress/dom-ready';
import { createRoot } from 'react-dom/client';
import { createHooks } from '@wordpress/hooks';
import { StrictMode } from 'react';
import App from './app';

export const hooks = createHooks();

// The WPGraphQL settings page wraps our section in `#wpgraphql_login_settings` while the WPGraphQL IDE renders the mount point registered by our settings field.
const CONTAINER_SELECTOR =
	'#wpgraphql_login_settings, #wp-graphql-headless-login-settings';

const mount = () => {
	const container =
		document.querySelector< HTMLElement >( CONTAINER_SELECTOR );

	if ( ! container || container.dataset[ 'wpglMounted' ] ) {
		return;
	}

	container.dataset[ 'wpglMounted' ] = 'true';

	createRoot( container ).render(
		<StrictMode>
			<App />
		</StrictMode>
	);
};

// Render the app.
domReady( () => {
	mount();

	// The IDE mounts its Settings tab on demand, so watch for the container.
	new MutationObserver( mount ).observe( document.body, {
		childList: true,
		subtree: true,
	} );
} );

wpGraphQLLogin.hooks = hooks;
