#!/usr/bin/env bash

# Switch to wp-graphql-woocommerce plugin and install dependencies
setup_plugins() {
	BASEDIR="$(pwd)"

	if ! $( wp plugin is-installed woocommerce --allow-root ); then
		wp plugin install woocommerce --ignore-requirements --allow-root
	fi

	if ! $( wp plugin is-installed wp-graphql-woocommerce --allow-root ); then
		wp plugin install https://github.com/wp-graphql/wp-graphql-woocommerce/releases/latest/download/wp-graphql-woocommerce.zip --ignore-requirements --allow-root
	fi

	cd "$BASEDIR" || exit 1
}

# Main setup flow
setup_plugins
