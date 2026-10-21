#!/bin/sh
# Démarrage du conteneur TextileCycle : prépare Laravel puis lance Apache.
set -e

# Port d'écoute : celui imposé par l'hébergeur (Render fournit PORT), 80 sinon.
PORT="${PORT:-80}"
sed -ri "s/^Listen [0-9]+/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# MySQL hébergé (Aiven) : connexion chiffrée avec le certificat CA fourni dans MYSQL_SSL_CA_CERT.
if [ -n "${MYSQL_SSL_CA_CERT:-}" ]; then
    CA_FILE=/var/www/html/storage/mysql-ca.pem
    # Accepte le certificat sur plusieurs lignes ou avec des « \n » écrits en toutes lettres.
    printf '%b\n' "$MYSQL_SSL_CA_CERT" > "$CA_FILE"
    chmod 644 "$CA_FILE"
    export MYSQL_ATTR_SSL_CA="$CA_FILE"
fi

# Base SQLite (démonstration en ligne) : le fichier est créé s'il n'existe pas.
if [ "${DB_CONNECTION:-}" = "sqlite" ]; then
    DB_FILE="${DB_DATABASE:-/var/www/html/database/database.sqlite}"
    mkdir -p "$(dirname "$DB_FILE")"
    touch "$DB_FILE"
    chown -R www-data:www-data "$(dirname "$DB_FILE")"
fi

# Lien public/storage vers storage/app/public (photos téléversées).
php artisan storage:link --force >/dev/null 2>&1 || true

# Préparation de la base :
#   DEMO_DONNEES=true     → base recréée et remplie à chaque démarrage (SQLite de démo)
#   INITIALISER_DEMO=true → migrations, et données de démo seulement si la base est vide (MySQL, rien n'est effacé)
#   RUN_MIGRATIONS=true   → migrations seules
# La base distante peut mettre quelques secondes à accepter les connexions : jusqu'à 10 essais.
preparer_base() {
    essai=1
    until "$@"; do
        if [ "$essai" -ge 10 ]; then
            echo "La base de données ne répond pas : abandon." >&2
            return 1
        fi
        echo "Base de données pas encore prête (essai $essai/10), nouvel essai dans 5 s…"
        essai=$((essai + 1))
        sleep 5
    done
}

# Sécurité : la remise à zéro n'est possible qu'avec SQLite, jamais sur une vraie base MySQL.
if [ "${DEMO_DONNEES:-false}" = "true" ] && [ "${DB_CONNECTION:-}" = "sqlite" ]; then
    preparer_base php artisan migrate:fresh --seed --force
elif [ "${INITIALISER_DEMO:-false}" = "true" ]; then
    preparer_base php artisan demo:initialiser
elif [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    preparer_base php artisan migrate --force
fi

# Caches de production : configuration, routes et vues compilées.
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Les fichiers créés par les commandes ci-dessus doivent rester accessibles à Apache.
chown -R www-data:www-data storage bootstrap/cache

exec "$@"
