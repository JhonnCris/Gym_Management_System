#!/bin/sh
set -e

cd /var/www/html

php artisan config:clear
php artisan migrate --force

exec apache2-foreground
