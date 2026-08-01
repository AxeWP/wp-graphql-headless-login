#!/usr/bin/env bash

PLUGIN_NAME="wp-graphql-headless-login"

# Run setup script inside wp-env environment
(npm run wp-env run cli -- --env-cwd=wp-content/plugins/${PLUGIN_NAME} -- bash bin/setup.sh) &
(npm run wp-env run tests-cli -- --env-cwd=wp-content/plugins/${PLUGIN_NAME} -- bash bin/setup.sh) &
wait
