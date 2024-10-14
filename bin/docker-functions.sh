#!/usr/bin/env bash
# Exit if any command fails.
set -e

# Common variables.
DOCKER_COMPOSE_FILE_OPTIONS="-f docker-compose.yml"

# These are the containers and values for the development site.
CONTAINER='wordpress'
DATABASE='mysql'

dc() {
	docker compose $DOCKER_COMPOSE_FILE_OPTIONS "$@"
}

mysql() {
	dc exec -T -e MYSQL_PWD="$WORDPRESS_DB_PASSWORD" "$DATABASE" mysql "$@"
}

##
# WordPress Container helper.
#
# Executes the given command in the wordpress container.
##
container() {
	dc exec -T "$CONTAINER" "$@"
}

echo "Docker functions loaded".
