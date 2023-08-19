import { render } from '@wordpress/element';
import { createHooks } from '@wordpress/hooks';
import App from './app';

export const hooks = createHooks();

// Render the app.
document.addEventListener( 'DOMContentLoaded', () => {
	const htmlOutput = document.getElementById( 'wpgraphql_login_settings' );

	if ( htmlOutput ) {
		render( <App />, htmlOutput );
	}
} );

wpGraphQLLogin.hooks = hooks;
