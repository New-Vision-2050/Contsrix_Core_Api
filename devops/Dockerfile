# Use an official PHP image with necessary extensions
FROM  mabou7agar/custom-php8-2

## Install Composer
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer
#
## Set working directory
WORKDIR /var/www
#
## Copy Laravel application
COPY . .
#
## Install Laravel dependencies
RUN composer install --no-dev --optimize-autoloader --no-scripts
#
## Set permissions
RUN chown -R www-data:www-data /var/www && chmod -R 755 /var/www
#
## Expose PHP-FPM port
EXPOSE 9000
#
## Start PHP-FPM
CMD ["php-fpm"]
