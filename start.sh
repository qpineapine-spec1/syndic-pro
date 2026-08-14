#!/bin/sh
set -e

# Génère la clé d'application si elle manque encore
php artisan key:generate --force --no-interaction || true

# Prépare le lien de stockage public (uploads, factures, etc.)
php artisan storage:link || true

# Applique les migrations sur la base configurée
php artisan migrate --force

# Optimise pour la production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Démarre le serveur PHP intégré, sur le port fourni par Railway
php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
