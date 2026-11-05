#!/bin/sh
set -eu
cache=/var/www/html/caches
uploads=/var/www/html/attachment
directories='session tpl logs backup upzip txt fonts import'
# Root keeps only CHOWN/SETUID/SETGID (no DAC_OVERRIDE/DAC_READ_SEARCH/FOWNER):
# it may re-own the volume roots and known top-level entries, but cannot write
# into or traverse www-data-owned private directories. Everything that writes
# therefore runs as www-data, so fresh volumes, restarts with populated 0700
# staging and caches created by older entrypoints all initialize the same way.
chown www-data:www-data "$cache" "$uploads"
for path in $directories install.lock; do
    if [ -e "$cache/$path" ] && [ ! -L "$cache/$path" ]; then
        chown www-data:www-data "$cache/$path"
    fi
done
for path in "$uploads/book" "$uploads/book/import"; do
    if [ -e "$path" ] && [ ! -L "$path" ]; then
        chown www-data:www-data "$path"
    fi
done
setpriv --reuid=www-data --regid=www-data --init-groups sh -eu -c '
for directory in $3; do
    mkdir -p "$1/$directory"
done
mkdir -p "$2/book/import"
printf "installed\n" > "$1/install.lock"
chmod 0700 "$1/import"
' sh "$cache" "$uploads" "$directories"
exec docker-php-entrypoint "$@"
