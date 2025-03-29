<?php
/**
 * Constants defined in this file are to help phpstan analyze code where constants outside the plugin (WordPress core constants, etc) are being used.
 *
 * @package WPGraphQL/Login
 */

define( 'WPGRAPHQL_LOGIN_PLUGIN_FILE', 'wp-graphql-headless-login.php' );
define( 'WPGRAPHQL_LOGIN_VERSION', '0.4.2' );
define( 'WPGRAPHQL_LOGIN_PLUGIN_DIR', '' );

// WordPress Constants.
define( 'AUTH_COOKIE', 'wordpress_' );
define( 'SECURE_AUTH_COOKIE', 'wordpress_sec_' );
define( 'COOKIEPATH', '/' );
define( 'SITECOOKIEPATH', '/' );
define( 'ADMIN_COOKIE_PATH', SITECOOKIEPATH . 'wp-admin' );
define( 'PLUGINS_COOKIE_PATH', '/wp-content/plugins' );
define( 'LOGGED_IN_COOKIE', 'wordpress_logged_in_' );
