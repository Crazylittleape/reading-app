FROM php:8.1-apache-bookworm
# zip (ZipArchive) and gd (JPEG/PNG/GIF/WebP decoding of imported images) are required by the EPUB importer. gd links the
# system libgd: PHP's bundled copy raises an uncatchable E_ERROR on corrupt WebP data instead of failing the decode.
# dom/libxml/xmlreader/fileinfo/mbstring ship with the base image and are verified below.
RUN apt-get update \
    && apt-get install -y --no-install-recommends libzip-dev libgd-dev \
    && docker-php-ext-configure gd --with-external-gd \
    && docker-php-ext-install -j2 mysqli pdo_mysql zip gd \
    && rm -rf /var/lib/apt/lists/* \
    && php -r 'foreach (array("dom", "libxml", "xmlreader", "fileinfo", "mbstring", "zip", "json", "gd") as $e) { if (!extension_loaded($e)) { fwrite(STDERR, "missing PHP extension: $e\n"); exit(1); } }' \
    && php -r 'foreach (array(IMG_JPG, IMG_PNG, IMG_GIF, IMG_WEBP) as $t) { if (!(imagetypes() & $t)) { fwrite(STDERR, "gd lacks image type $t\n"); exit(1); } }' \
    && a2enmod rewrite headers \
    && sed -i 's/^Listen 80$/Listen 8080/' /etc/apache2/ports.conf
COPY apache.conf /etc/apache2/sites-available/000-default.conf
COPY php.ini /usr/local/etc/php/conf.d/reading.ini
COPY scripts/container-entrypoint.sh /usr/local/bin/reading-entrypoint
RUN chmod +x /usr/local/bin/reading-entrypoint
ENTRYPOINT ["reading-entrypoint"]
CMD ["apache2-foreground"]
EXPOSE 8080
