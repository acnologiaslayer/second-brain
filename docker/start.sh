#!/bin/sh
set -e
PORT="${PORT:-10000}"
sed -i "s/Listen 80$/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Managed MySQL (e.g. Aiven) requires TLS: paste the CA certificate into DB_SSL_CA_PEM.
if [ -n "$DB_SSL_CA_PEM" ]; then
  printf '%s\n' "$DB_SSL_CA_PEM" > /var/www/html/storage/db-ca.pem
  export MYSQL_ATTR_SSL_CA=/var/www/html/storage/db-ca.pem
fi

php artisan package:discover --ansi
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan migrate --force
chown -R www-data:www-data storage bootstrap/cache
exec apache2-foreground
