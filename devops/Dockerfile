# Use an official PHP image with necessary extensions
FROM mabou7agar/custom-nginx-php8-2

# RUN docker-php-ext-install sockets \
#     && docker-php-ext-enable sockets

#RUN apt-get update && apt-get install -y \
#        libmagickwand-dev --no-install-recommends \
#    && pecl install imagick \
#    && docker-php-ext-install sockets \
#    && docker-php-ext-enable imagick sockets

# Install zip extension, curl, and ca-certificates for RoadRunner download
RUN apt-get update && apt-get install -y \
    libzip-dev zip unzip curl ca-certificates \
    && docker-php-ext-install zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

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

RUN echo "upload_max_filesize=500M" > /usr/local/etc/php/conf.d/uploads.ini && \
    echo "post_max_size=500M" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "memory_limit=512M" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "max_execution_time=300" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "max_input_time=300" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "output_buffering=Off" >> /usr/local/etc/php/conf.d/uploads.ini

RUN mkdir -p storage/framework/sessions && \
    mkdir -p storage/framework/views && \
    mkdir -p storage/framework/cache && \
    mkdir -p storage/logs && \
    chmod -R 775 storage/framework && \
    chmod -R 775 storage/logs

RUN mkdir -p bootstrap/cache && \
   chmod -R 775 bootstrap/cache

## Install Laravel dependencies
RUN composer install --no-scripts

# Download RoadRunner binary with retry logic and diagnostics
RUN curl --version && \
    ./vendor/bin/rr get-binary --location /usr/local/bin || \
    (echo "RoadRunner download failed. Retrying..." && \
     sleep 5 && \
     ./vendor/bin/rr get-binary --location /usr/local/bin)

EXPOSE 80

# Add health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
    CMD curl -f http://localhost/health || exit 1

# Set permissions
RUN chown -R www-data:www-data /var/www \
    && chmod +x /usr/bin/supervisord

RUN chmod +x devops/entrypoint.sh

ENTRYPOINT ["devops/entrypoint.sh"]
# Start Supervisor to manage NGINX and PHP-FPM
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
