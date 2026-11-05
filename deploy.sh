#!/usr/bin/env bash
# Deploy ottimizzato. Non esegue migrazioni: il DB è legacy e va gestito a parte.
set -euo pipefail

cd "$(dirname "$0")"

composer install --no-dev --optimize-autoloader --no-interaction
npm ci
npm run build

php artisan down --retry=10 || true

php artisan optimize:clear
php artisan optimize
php artisan view:cache
php artisan event:cache
php artisan storage:link || true

# Applica il nuovo codice ai worker delle code
php artisan queue:restart

php artisan up
