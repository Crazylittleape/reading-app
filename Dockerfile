FROM php:8.1-apache-bookworm
RUN docker-php-ext-install -j2 mysqli pdo_mysql \
    && a2enmod rewrite headers \
    && sed -i 's/^Listen 80$/Listen 8080/' /etc/apache2/ports.conf
COPY apache.conf /etc/apache2/sites-available/000-default.conf
COPY php.ini /usr/local/etc/php/conf.d/reading.ini
COPY scripts/container-entrypoint.sh /usr/local/bin/reading-entrypoint
RUN chmod +x /usr/local/bin/reading-entrypoint
ENTRYPOINT ["reading-entrypoint"]
CMD ["apache2-foreground"]
EXPOSE 8080
