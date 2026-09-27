#!/usr/bin/env bash

if [[ ! -f ".env" ]]; then
	echo "No .env file was detected. .env.dist has been copied to .env"
	echo "Open the .env file and enter values to match your local environment"
	cp .env.dist .env
fi

source .env

# Switch to wp-graphql-woocommerce plugin and install dependencies
setup_plugins() {
	BASEDIR="$(pwd)"

	if ! $( wp plugin is-installed woocommerce --allow-root ); then
		wp plugin install woocommerce --allow-root
	fi

	if ! $( wp plugin is-installed wp-graphql-woocommerce --allow-root ); then
		wp plugin install https://github.com/wp-graphql/wp-graphql-woocommerce/releases/latest/wp-graphql-woocommerce.zip --allow-root
	fi

	cd "$BASEDIR" || exit 1
}

# Post-setup: activate plugin, configure WordPress, export DB dump
post_setup() {
	echo "Running post-setup tasks..."

	# Deactivate woo plugins because we don't trust them.
	wp plugin deactivate woocommerce --allow-root
	wp plugin deactivate wp-graphql-woocommerce --allow-root

	# Set pretty permalinks.
	wp rewrite structure '/%year%/%monthnum%/%postname%/' --hard --allow-root
	wp rewrite flush --allow-root

	# Disable Update Checks
	wp config set WP_AUTO_UPDATE_CORE false --raw --type=constant --quiet --allow-root
	wp config set AUTOMATIC_UPDATER_DISABLED true --raw --type=constant --quiet --allow-root
}

# Main setup flow
setup_plugins
post_setup
