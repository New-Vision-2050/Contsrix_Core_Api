# Official PHP image (always pulled at latest patch via --pull)
FROM php:8.4-cli-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
    nginx supervisor git curl ca-certificates zip unzip \
    libzip-dev libpng-dev libjpeg-dev libfreetype6-dev libwebp-dev \
    libicu-dev libonig-dev libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" zip gd bcmath intl exif pcntl sockets pdo_mysql mysqli opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && ln -sf /dev/stdout /var/log/nginx/access.log \
    && ln -sf /dev/stderr /var/log/nginx/error.log \
    && mkdir -p /etc/nginx/sites-enabled /var/log/nginx \
    && rm -f /etc/nginx/sites-enabled/default \
    && apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/pear

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN php -r '$v=PHP_VERSION; $min=["8.2"=>"8.2.35","8.3"=>"8.3.36","8.4"=>"8.4.27","8.5"=>"8.5.12"]; $b=PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION; if(!isset($min[$b])||version_compare($v,$min[$b],"<")){fwrite(STDERR,"Vulnerable or unsupported PHP $v (RCE CVEs). Update base image.\n");exit(1);} echo "PHP $v OK\n";'

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

RUN { \
    echo "opcache.enable=1"; \
    echo "opcache.enable_cli=1"; \
    echo "opcache.memory_consumption=256"; \
    echo "opcache.interned_strings_buffer=32"; \
    echo "opcache.max_accelerated_files=30000"; \
    echo "opcache.validate_timestamps=0"; \
    echo "realpath_cache_size=4096K"; \
    echo "realpath_cache_ttl=600"; \
    } > /usr/local/etc/php/conf.d/performance.ini

RUN mkdir -p storage/framework/sessions && \
    mkdir -p storage/framework/views && \
    mkdir -p storage/framework/cache && \
    mkdir -p storage/logs && \
    chmod -R 775 storage/framework && \
    chmod -R 775 storage/logs

RUN mkdir -p bootstrap/cache && \
   chmod -R 775 bootstrap/cache

## Install Laravel dependencies
RUN composer install --no-scripts --optimize-autoloader

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
