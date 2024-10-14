#!/usr/bin/env bash

set -eu

##
# Use this script through NPM scripts in the package.json, after installing the NPM and Composer Dependencies and updating the .env file.
##
print_usage_instructions() {
	echo "Usage: ./build-docker.sh [OPTIONS]"
	echo "Options:"
	echo "  -c    Build without cache"
	echo "  -h    Display this help message"
	echo ""
	echo "Example use:"
	echo "  npm run docker:build"
	echo ""
	echo "  WP_VERSION=6.6 PHP_VERSION=8.2 npm run docker:build -- - c"
	echo ""
	echo "  WP_VERSION=6.6 PHP_VERSION=8.2 bin/build-docker.sh -- c"
	exit 1
}


if [[ ! -f ".env" ]]; then
	echo "No .env file was detected. .env.dist has been copied to .env"
	echo "Open the .env file and enter values to match your local environment"
	cp .env.dist .env
fi

source .env

while getopts ":ch" opt; do
	case ${opt} in
	c)
		echo "Build with  --no-cache"
		BUILD_NO_CACHE=--no-cache
		;;
	h)
		print_usage_instructions
		;;
	\?)
		echo "Invalid flag: -$OPTARG" 1>&2
		exit 1
		;;
	*)
		print_usage_instructions
		;;
	esac
done


TAG=${TAG:-latest}
WP_VERSION=${WP_VERSION:-6.6}
PHP_VERSION=${PHP_VERSION:-8.2}

BUILD_NO_CACHE=${BUILD_NO_CACHE:-}

docker build $BUILD_NO_CACHE \
	-t "${PLUGIN_SLUG}:${TAG}-wp${WP_VERSION}-php${PHP_VERSION}" \
	--build-arg WP_VERSION="${WP_VERSION}" \
	--build-arg PHP_VERSION="${PHP_VERSION}" \
	./.docker
