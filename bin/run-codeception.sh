#!/usr/bin/env bash

ORIGINAL_PATH=$(pwd)
BASEDIR=$(dirname "$0")
PROJECT_DIR="$WORDPRESS_ROOT_DIR/wp-content/plugins/$PLUGIN_SLUG"


source "${BASEDIR}/_lib.sh"

echo -e "$(status_message "WordPress: ${WP_VERSION} PHP: ${PHP_VERSION}")"

##
# Set up before running tests.
##
setup_before() {
	cd "$PROJECT_DIR"

	# Download c3 for testing.
	if [ ! -f "c3.php" ]; then
			echo "Downloading Codeception's c3.php"
			curl -L 'https://raw.github.com/Codeception/c3/2.0/c3.php' > "c3.php"
	fi

	# Enable XDebug or PCOV for code coverage.
	if [[ "$COVERAGE" == '1' ]]; then
		if [[ "$USING_XDEBUG" == '1' ]]; then
			echo "Enabling XDebug 3"
			cp /usr/local/etc/php/conf.d/disabled/docker-php-ext-xdebug.ini /usr/local/etc/php/conf.d/
			echo "xdebug.mode=coverage" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
		else
			echo "Using pcov/clobber for code coverage"
			docker-php-ext-enable pcov
			echo "pcov.enabled=1" >> /usr/local/etc/php/conf.d/docker-php-ext-pcov.ini
			echo "pcov.directory=${PROJECT_DIR}" >> /usr/local/etc/php/conf.d/docker-php-ext-pcov.ini
			COMPOSER_MEMORY_LIMIT=-1 composer require pcov/clobber --dev
			vendor/bin/pcov clobber
		fi
	elif [[ -f /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ]]; then
		echo "Disabling XDebug"
		rm /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
	fi

	# Install the PHP dev-dependencies.
	echo "Running composer install"
	COMPOSER_MEMORY_LIMIT=-1 composer install

# Set output permission
	echo "Setting Codeception output directory permissions"
	chmod 777 -R tests/_output
}

##
# Run tests.
##
run_tests() {
	if [[ -n "$DEBUG" ]]; then
		local debug="--debug"
	fi

	local suites=$1
	if [[ -z "$SUITES" ]]; then
		echo "No test suites specified. Must specify variable SUITES."
		exit 1
	fi

	if [[ -n "$COVERAGE" ]]; then
		local coverage="--coverage --coverage-xml $suites-coverage.xml"
	fi

	# If maintenance mode is active, de-activate it
	if $(wp maintenance-mode is-active --allow-root); then
		echo "Deactivating maintenance mode"
		wp maintenance-mode deactivate --allow-root
	fi

	# Suites is the comma separated list of suites/tests to run.
	echo "Running Test Suite $suites"
	cd "$PROJECT_DIR"
  XDEBUG_MODE=coverage vendor/bin/codecept run -c codeception.dist.yml ${suites} ${coverage:-} ${debug:-} --no-exit
}

##
# Clean up after running tests.
##
cleanup_after() {
	cd "$PROJECT_DIR"

	# Remove c3.php if it exists and cleanup is not skipped
	if [ -f "c3.php" ] && [ "$SKIP_TESTS_CLEANUP" != "true" ]; then
		echo "Removing Codeception's c3.php"
		rm "c3.php"
	fi

	# Disable XDebug or PCOV if they were enabled for code coverage
	if [[ "$COVERAGE" == '1' ]]; then
		if [[ "$USING_XDEBUG" == '1' ]]; then
			echo "Disabling XDebug 3"
			rm /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
		else
			echo "Disabling pcov/clobber"
			docker-php-ext-disable pcov
			sed -i '/pcov.enabled=1/d' /usr/local/etc/php/conf.d/docker-php-ext-pcov.ini
			sed -i '/pcov.directory=${PROJECT_DIR}/d' /usr/local/etc/php/conf.d/docker-php-ext-pcov.ini
			COMPOSER_MEMORY_LIMIT=-1 composer remove pcov/clobber --dev
		fi
	fi

	# Set output permission back to default
	echo "Resetting Codeception output directory permissions"
	chmod 777 -R tests/_output
}

# Prepare to run tests.
echo "Setting up for Codeception tests"
setup_before


# Run the tests
run_tests $SUITES

# Clean up after running tests.
echo "Cleaning up after Codeception tests"
cleanup_after

# Check results and exit accordingly.
if [ -f "tests/_output/failed" ]; then
	echo "Uh oh, Codeception tests failed."
	exit 1
else
	echo "Woohoo! Codeception tests completed succesfully!"
fi
