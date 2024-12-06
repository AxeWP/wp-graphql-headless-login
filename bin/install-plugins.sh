#!/bin/bash

# Exit if any command fails.
set -e

source ".env"

## Add the `wp plugin install` and `wp plugin activate` commands here for any external plugins that this one depends on for testing.
#
# Example: Install and activate WPGraphQL from the .org plugin repository.
#
# if ! $( wp plugin is-installed wp-graphql --allow-root ); then
#   wp plugin install wp-graphql --allow-root
# fi
# wp plugin activate wp-graphql  --allow-root
#
# Example: Install and activate the WPGraphQL Upload plugin from GitHub.
#
# if ! $( wp plugin is-installed wp-graphql-upload --allow-root ); then
#   wp plugin install https://github.com/dre1080/wp-graphql-upload/archive/refs/heads/master.zip --allow-root
# fi
# wp plugin activate wp-graphql-upload --allow-root

# We use an old version of WPGraphQL Content Blocks for testing the PUC.

# WPGraphQL
install_wpgraphql() {
	if ! $( wp plugin is-installed wp-graphql --allow-root ); then
		wp plugin install wp-graphql --allow-root
	fi
	wp plugin activate wp-graphql --allow-root
}

# WooCommerce
install_woocommerce() {
	if ! $( wp plugin is-installed woocommerce --allow-root ); then
		wp plugin install woocommerce --allow-root
	fi
	wp plugin activate woocommerce --allow-root

	if ! $( wp plugin is-installed wp-graphql-woocommerce --allow-root ); then
		# TODO: revert to latest after WooGraphQL release issues in 0.21.1 are resolved.
		wp plugin install https://github.com/wp-graphql/wp-graphql-woocommerce/releases/download/v0.21.0/wp-graphql-woocommerce.zip --allow-root

		# Install composer deps
		cd $WORDPRESS_ROOT_DIR/wp-content/plugins/wp-graphql-woocommerce
		composer install --no-dev --no-interaction --no-progress --optimize-autoloader

		cd $WORDPRESS_ROOT_DIR
		wp plugin activate wp-graphql-woocommerce --allow-root
	fi
}

# Run the install functions.
cd $WORDPRESS_ROOT_DIR

install_wpgraphql
if [ "${INCLUDE_EXTENSIONS}" = "true" ]; then
	install_woocommerce
fi
