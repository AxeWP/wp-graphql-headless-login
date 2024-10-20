#!/bin/bash

# Exit if any command fails.
set -e

# Wait for the database
dockerize -wait tcp://"${WORDPRESS_DB_HOST}":3306 -timeout 1m

# Get the current user

cd "$WORDPRESS_ROOT_DIR/wp-content/plugins/$PLUGIN_SLUG"

# Load NVM
source $NVM_DIR/nvm.sh
nvm use $NODE_VERSION

# Setup the test environment
chmod +x ./bin/install-test-env.sh

bash -c "./bin/install-test-env.sh"

# Go back to the root directory
cd "$WORDPRESS_ROOT_DIR"
