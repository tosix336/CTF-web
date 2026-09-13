FROM php:8.2-apache
RUN docker-php-ext-install mysqli && a2enmod rewrite
COPY src/ /var/www/html/
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh \
    && printf '%s\n' '<Directory /var/www/html/uploads>' '    AllowOverride All' '</Directory>' > /etc/apache2/conf-available/uploads.conf \
    && a2enconf uploads
RUN chown -R www-data:www-data /var/www/html/uploads
ENTRYPOINT ["/entrypoint.sh"]
