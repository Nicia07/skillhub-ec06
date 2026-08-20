#!/bin/sh
set -e

if [ ! -f /var/www/html/.env ]; then
    cp /var/www/html/.env.example /var/www/html/.env
fi

php artisan config:clear

if [ -z "${APP_KEY}" ]; then
    php artisan key:generate --force
fi

if [ -z "${JWT_SECRET}" ]; then
    php artisan jwt:secret --force
fi

# Attend que MySQL accepte les connexions avant de migrer.
until php artisan migrate --force; do
    echo "En attente de la base de donnees..."
    sleep 3
done

exec "$@"
