# Use an official PHP image with necessary extensions
FROM mabou7agar/custom-nginx-php8-2

# RUN docker-php-ext-install sockets \
#     && docker-php-ext-enable sockets

#RUN apt-get update && apt-get install -y \
#        libmagickwand-dev --no-install-recommends \
#    && pecl install imagick \
#    && docker-php-ext-install sockets \
#    && docker-php-ext-enable imagick sockets

# Install zip extension
RUN apt-get update && apt-get install -y \
    libzip-dev zip unzip \
    && docker-php-ext-install zip \

ARG CACHEBUST=1
## Set working directory
WORKDIR /var/www
#
## Copy Laravel application

COPY . .
#


COPY devops/nginx/nginx.conf /etc/nginx/nginx.conf
COPY devops/nginx/sites-enabled/default.conf /etc/nginx/sites-enabled/default.conf
COPY devops/supervisord.conf /etc/supervisord.conf

RUN mkdir -p storage/framework/sessions && \
    mkdir -p storage/framework/views && \
    mkdir -p storage/framework/cache && \
    mkdir -p storage/logs && \
    chmod -R 775 storage/framework && \
    chmod -R 775 storage/logs

RUN mkdir -p bootstrap/cache && \
   chmod -R 775 bootstrap/cache

## Install Laravel dependencies
RUN composer install --optimize-autoloader --no-scripts

EXPOSE 80

# Set permissions
RUN chown -R www-data:www-data /var/www \
    && chmod +x /usr/bin/supervisord

RUN chmod +x devops/entrypoint.sh

ENTRYPOINT ["devops/entrypoint.sh"]
# Start Supervisor to manage NGINX and PHP-FPM
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
