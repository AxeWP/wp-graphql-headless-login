import domReady from '@wordpress/dom-ready';
import { createRoot } from 'react-dom/client';
import { createHooks } from '@wordpress/hooks';
import { StrictMode } from 'react';
import App from './app';

export const hooks = createHooks();

// The WPGraphQL settings page wraps our section in `#wpgraphql_login_settings` while the WPGraphQL IDE renders the mount point registered by our settings field.
const CONTAINER_SELECTOR =
	'#wpgraphql_login_settings, #wp-graphql-headless-login-settings';

/** Mounts the app, returning whether the container is in the DOM. */
const mount = () => {
	const container =
		document.querySelector< HTMLElement >( CONTAINER_SELECTOR );

	if ( ! container ) {
		return false;
	}

	if ( ! container.dataset[ 'wpglMounted' ] ) {
		container.dataset[ 'wpglMounted' ] = 'true';

		createRoot( container ).render(
			<StrictMode>
				<App />
			</StrictMode>
		);
	}

	return true;
};

// Render the app.
domReady( () => {
	// On the settings screen the container is already server-rendered, so there's nothing to watch for.
	if ( mount() ) {
		return;
	}

	// Elsewhere, it's loaded on-demand.
	new MutationObserver( mount ).observe( document.body, {
		childList: true,
		subtree: true,
	} );
} );

wpGraphQLLogin.hooks = hooks;
