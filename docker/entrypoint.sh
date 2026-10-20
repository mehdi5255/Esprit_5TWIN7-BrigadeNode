#!/bin/sh
# Démarrage du conteneur TextileCycle : prépare Laravel puis lance Apache.
set -e

# Lien public/storage vers storage/app/public (photos téléversées).
php artisan storage:link --force >/dev/null 2>&1 || true

# Migrations à la demande (RUN_MIGRATIONS=true), une fois la base disponible.
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force
fi

# Caches de production : configuration, routes et vues compilées.
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
