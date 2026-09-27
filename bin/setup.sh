#!/usr/bin/env bash

# Install WooCommerce and WPGraphQL for WooCommerce
setup_plugins() {
	if ! $( wp plugin is-installed woocommerce --allow-root ); then
		wp plugin install woocommerce --ignore-requirements --allow-root
	fi

	if ! $( wp plugin is-installed wp-graphql-woocommerce --allow-root ); then
		wp plugin install https://github.com/wp-graphql/wp-graphql-woocommerce/releases/latest/download/wp-graphql-woocommerce.zip --ignore-requirements --allow-root
	fi
}

# Main setup flow
setup_plugins
