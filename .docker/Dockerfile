###############################################################################
# Pre-configured WordPress Installation w/ WPGraphQL, and Headless Login for WPGraphQL #
# For testing only, use in production not recommended. #
###############################################################################

# Use build args to get the right wordpress + php image
ARG WP_VERSION
ARG PHP_VERSION

FROM wordpress:${WP_VERSION:-6.8}-php${PHP_VERSION:-8.2}-apache AS base

# Needed to specify the build args again after the FROM command.
ARG WP_VERSION
ARG PHP_VERSION

# Save the build args for use by the runtime environment
ENV WP_VERSION=${WP_VERSION}
ENV PHP_VERSION=${PHP_VERSION}

LABEL author=axepress
LABEL author_uri=https://github.com/AxeWP

SHELL [ "/bin/bash", "-c" ]

# Install required packages
RUN apt-get update && \
    apt-get -y install \
    mariadb-client \
    git zip unzip

# Needed for Codeception WPDB test integration.
RUN docker-php-ext-install pdo pdo_mysql

# Install PCOV
# This is needed for Codeception / PHPUnit to track code coverage
RUN pecl install pcov

# Install composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
RUN chmod +x /usr/local/bin/composer

# Install WP-CLI
RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
    && chmod +x wp-cli.phar \
    && mv wp-cli.phar /usr/local/bin/wp

# Install nvm, Node.js, and npm
ENV NVM_DIR=/usr/local/nvm
ENV NODE_VERSION=20

RUN mkdir -p $NVM_DIR
RUN curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.1/install.sh | bash \
    && . $NVM_DIR/nvm.sh \
    && nvm install $NODE_VERSION \
    && nvm use $NODE_VERSION \
    && nvm alias default $NODE_VERSION \
    && npm install -g npm

# Setup the container for testing
COPY init-docker.sh /usr/local/bin/init-docker.sh
RUN chmod +x /usr/local/bin/init-docker.sh
