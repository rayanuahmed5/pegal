# Pegal app image: PHP 8.3 with the MySQL extension and Composer.
# Used by compose.yaml, see README section "Run with Docker".
FROM php:8.3-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install pdo_mysql

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . /app

# vendor/ is built here, not copied from the host (see .dockerignore)
RUN composer install --no-interaction --prefer-dist --no-progress \
    && chmod +x docker/entrypoint.sh

EXPOSE 8000
ENTRYPOINT ["docker/entrypoint.sh"]
