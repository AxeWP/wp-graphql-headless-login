#!/bin/bash

# Exit if any command fails.
set -e

cd "$WORDPRESS_ROOT_DIR/wp-content/plugins/$PLUGIN_SLUG"

git config --global --add safe.directory $(pwd)
echo "Git directory set as safe."

# Load NVM
source $NVM_DIR/nvm.sh
nvm use $NODE_VERSION

# Wait for MySQL to be healthy
echo "Waiting for MySQL service to be ready..."
until mysqladmin ping -h mysql -u root -proot --silent; do
  sleep 5
done
echo "MySQL service is ready."

# Setup the test environment

chmod +x ./bin/install-test-env.sh

bash -c "./bin/install-test-env.sh"

echo "Setting permissions"
chmod -R 777 "$WORDPRESS_ROOT_DIR/wp-content/plugins/$PLUGIN_SLUG"

# Go back to the root directory
cd "$WORDPRESS_ROOT_DIR"

