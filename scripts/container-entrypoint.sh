#!/bin/sh
set -eu
for directory in session tpl logs backup upzip txt fonts; do
    mkdir -p "/var/www/html/caches/$directory"
done
mkdir -p /var/www/html/attachment/book/import
printf 'installed\n' > /var/www/html/caches/install.lock
chown -R www-data:www-data /var/www/html/caches /var/www/html/attachment
exec docker-php-entrypoint "$@"
