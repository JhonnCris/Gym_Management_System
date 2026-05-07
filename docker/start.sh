#!/bin/sh
set -e

cd /var/www/html

echo "Starting container bootstrap..."

if [ -z "${DB_CONNECTION:-}" ]; then
    echo "DB_CONNECTION is not set."
    exit 1
fi

if [ "$DB_CONNECTION" != "mysql" ] && [ "$DB_CONNECTION" != "mariadb" ]; then
    echo "Refusing to run migrations on unsupported DB_CONNECTION=$DB_CONNECTION"
    exit 1
fi

php artisan config:clear
php -r 'require "vendor/autoload.php"; $app = require "bootstrap/app.php"; $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class); $kernel->bootstrap(); $default = config("database.default"); $conn = config("database.connections.".$default); echo "Resolved database connection: ".$default.PHP_EOL; echo "Resolved database host: ".($conn["host"] ?? "").PHP_EOL; echo "Resolved database name: ".($conn["database"] ?? "").PHP_EOL;'
php artisan migrate --force --database="$DB_CONNECTION"

echo "Migration step finished. Starting Apache..."
exec apache2-foreground
