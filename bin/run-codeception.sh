#!/usr/bin/env bash

ORIGINAL_PATH=$(pwd)
BASEDIR=$(dirname "$0")

source "${BASEDIR}/_lib.sh"
source "${BASEDIR}/docker-functions.sh"

echo -e "$(status_message "WordPress: ${WP_VERSION} PHP: ${PHP_VERSION}")"

# Exits with a status of 0 (true) if provided version number is higher than proceeding numbers.
version_gt() {
    test "$(printf '%s\n' "$@" | sort -V | head -n 1)" != "$1";
}

##
# Set up before running tests.
##
setup_before() {
	PROJECT_DIR="$WORDPRESS_ROOT_DIR/wp-content/plugins/$PLUGIN_SLUG"

	cd "$PROJECT_DIR"

	# Download c3 for testing.
	if [ ! -f "c3.php" ]; then
			echo "Downloading Codeception's c3.php"
			curl -L 'https://raw.github.com/Codeception/c3/2.0/c3.php' > "c3.php"
	fi

	# Install the PHP dependencies
	echo "Running composer install"
	COMPOSER_MEMORY_LIMIT=-1 composer install

	# Install pcov/clobber if PHP7.1+
	if version_gt $PHP_VERSION 7.0 && [[ -n "$COVERAGE" ]] && [[ -z "$USING_XDEBUG" ]]; then
			echo "Using pcov/clobber for codecoverage"
			docker-php-ext-enable pcov
			echo "pcov.enabled=1" >> /usr/local/etc/php/conf.d/docker-php-ext-pcov.ini
			echo "pcov.directory = ${PROJECT_DIR}" >> /usr/local/etc/php/conf.d/docker-php-ext-pcov.ini
			COMPOSER_MEMORY_LIMIT=-1 composer require pcov/clobber --dev
			vendor/bin/pcov clobber
	elif [[ -n "$COVERAGE" ]] && [[ -n "$USING_XDEBUG" ]]; then
			echo "Using XDebug for codecoverage"
	fi
}

##
# Run tests.
##
run_tests() {
	if [[ -n "$DEBUG" ]]; then
		local debug="--debug"
	fi

	local suites=$1
	if [[ -z "$suites" ]]; then
		echo "No test suites specified. Must specify variable SUITES."
		exit 1
	fi

	if [[ -n "$COVERAGE" ]]; then
		local coverage="--coverage --coverage-xml $suites-coverage.xml"
	fi

	# If maintenance mode is active, de-activate it
	if $(container wp maintenance-mode is-active --allow-root); then
		echo "Deactivating maintenance mode"
		container wp maintenance-mode deactivate --allow-root
	fi

	# Suites is the comma separated list of suites/tests to run.
	echo "Running Test Suite $suites"
	container bash -c "cd wp-content/plugins/$PLUGIN_SLUG && vendor/bin/codecept run -c codeception.dist.yml ${suites} ${coverage:-} ${debug:-} --no-exit"
}

##
# Clean up after running tests.
##
cleanup_after() {
	PROJECT_DIR="$WORDPRESS_ROOT_DIR/wp-content/plugins/$PLUGIN_SLUG"

	cd "$PROJECT_DIR"

	# Remove c3.php
	if [ -f "$PROJECT_DIR/c3.php" ] && [ "$SKIP_TESTS_CLEANUP" != "true" ]; then
			echo "Removing Codeception's c3.php"
			rm -rf "$PROJECT_DIR/c3.php"
	fi

	# Clean coverage.xml and clean up PCOV configurations.
	if [ -f "tests/_output/coverage.xml" ] && [[ -n "$COVERAGE" ]]; then
			echo 'Cleaning coverage.xml for deployment'.
			pattern="$PROJECT_DIR/"
			sed -i "s~$pattern~~g" "tests/_output/coverage.xml"

			# Remove pcov/clobber
			if version_gt $PHP_VERSION 7.0 && [[ -z "$SKIP_TESTS_CLEANUP" ]] && [[ -z "$USING_XDEBUG" ]]; then
					echo 'Removing pcov/clobber.'
					vendor/bin/pcov unclobber
					COMPOSER_MEMORY_LIMIT=-1 composer remove --dev pcov/clobber
					rm /usr/local/etc/php/conf.d/docker-php-ext-pcov.ini
			fi
	fi

	# Set public test result files permissions.
	if [ -n "$(ls tests/_output)" ]; then
			echo "Setting result files permissions."
			chmod 777 -R tests/_output/*
	fi
}

# Prepare to run tests.
echo "Setting up for Codeception tests"
container bash -c "$(declare -f version_gt); $(declare -f setup_before); setup_before"

# Set output permission
echo "Setting Codeception output directory permissions"
container bash -c "chmod 777 wp-content/plugins/$PLUGIN_SLUG/tests/_output"

# Run the tests
run_tests $SUITES

# Clean up after running tests.
echo "Cleaning up after Codeception tests"
container bash -c "$(declare -f version_gt); $(declare -f cleanup_after); cleanup_after"

# Check results and exit accordingly.
if [ -f "tests/_output/failed" ]; then
    echo "Uh oh, Codeception tests failed."
    exit 1
else
    echo "Woohoo! Codeception tests completed succesfully!"
fi
