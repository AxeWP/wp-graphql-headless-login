#!/usr/bin/env bash

# Switch to wp-graphql-woocommerce plugin and install dependencies
setup_plugins() {
	BASEDIR="$(pwd)"

	cd ../wp-graphql-woocommerce || exit 1
	if [ ! -d "vendor" ]; then
		echo "Installing WPGraphQL WooCommerce dependencies..."
		composer install --no-interaction --no-dev --optimize-autoloader
	fi

	cd "$BASEDIR" || exit 1
}

# Main setup flow
setup_plugins
