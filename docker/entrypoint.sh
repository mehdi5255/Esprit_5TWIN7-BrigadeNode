#!/bin/sh
# Démarrage du conteneur TextileCycle : prépare Laravel puis lance Apache.
set -e

# Port d'écoute : celui imposé par l'hébergeur (Render fournit PORT), 80 sinon.
PORT="${PORT:-80}"
sed -ri "s/^Listen [0-9]+/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Base SQLite (démonstration en ligne) : le fichier est créé s'il n'existe pas.
if [ "${DB_CONNECTION:-}" = "sqlite" ]; then
    DB_FILE="${DB_DATABASE:-/var/www/html/database/database.sqlite}"
    mkdir -p "$(dirname "$DB_FILE")"
    touch "$DB_FILE"
    chown -R www-data:www-data "$(dirname "$DB_FILE")"
fi

# Lien public/storage vers storage/app/public (photos téléversées).
php artisan storage:link --force >/dev/null 2>&1 || true

# Données de démonstration (DEMO_DONNEES=true) : base recréée et remplie à chaque démarrage.
# Sinon, migrations à la demande (RUN_MIGRATIONS=true).
if [ "${DEMO_DONNEES:-false}" = "true" ]; then
    php artisan migrate:fresh --seed --force
elif [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force
fi

# Caches de production : configuration, routes et vues compilées.
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Les fichiers créés par les commandes ci-dessus doivent rester accessibles à Apache.
chown -R www-data:www-data storage bootstrap/cache

exec "$@"
