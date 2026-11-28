#!/bin/sh
# Render start-up: listen on Render's port, prepare writable/, apply new migrations, start Apache.
set -e

PORT="${PORT:-10000}"
sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

mkdir -p writable/cache writable/logs writable/session writable/uploads writable/debugbar public/uploads/covers
chown -R www-data:www-data writable public/uploads

# Create / update tables (does nothing if the database is already up to date).
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php spark migrate --all || echo "WARNING: migrations failed; check the database settings."
fi

exec apache2-foreground
