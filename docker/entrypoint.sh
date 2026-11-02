#!/bin/sh
# Start script of the app container: wait for MySQL, create tables, load demo data once, serve.
set -e

# no .env inside the container, the settings come from compose.yaml;
# a fresh key is made on first start (the demo has no data that needs a stable key)
if [ -z "$APP_KEY" ]; then
    export APP_KEY="$(php artisan key:generate --show)"
fi

# Laravel needs these folders to be writable; .dockerignore may have left them out of the image
mkdir -p storage/logs storage/framework/cache/data storage/framework/sessions storage/framework/views bootstrap/cache

# compose waits for the database to be healthy, this loop only covers a slow first connect
tries=0
until php artisan migrate --force; do
    tries=$((tries + 1))
    if [ "$tries" -ge 15 ]; then
        echo "MySQL did not become ready, giving up." >&2
        exit 1
    fi
    echo "Waiting for MySQL ($tries/15) ..."
    sleep 2
done

# skips by itself when the demo users already exist, so restarts keep the data
php artisan db:seed --force

exec php artisan serve --host=0.0.0.0 --port=8000
