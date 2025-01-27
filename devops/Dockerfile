# Use an official PHP image with necessary extensions
FROM mabou7agar/custom-nginx-php8-2

## Install system dependencies
#RUN apt-get update && apt-get install -y \
#    nginx \
#    supervisor \
#    bash \
#    git \
#    unzip \
#    curl \
#    libpng-dev \
#    libonig-dev \
#    libxml2-dev \
#    zip \
#    && docker-php-ext-install \
#    opcache \
#    intl \
#    pdo_mysql \
#    mbstring \
#    exif \
#    pcntl \
#    bcmath \
#    gd \
#    && pecl install redis \
#    && docker-php-ext-enable redis

## Install Composer
#COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer
#
## Set working directory
WORKDIR /var/www
#
## Copy Laravel application
COPY . .
#
## Install Laravel dependencies
RUN composer install --no-dev --optimize-autoloader --no-scripts

COPY devops/nginx/nginx.conf /etc/nginx/nginx.conf
COPY devops/nginx/sites-enabled/default.conf /etc/nginx/sites-enabled/default.conf
COPY devops/supervisord.conf /etc/supervisord.conf

RUN mkdir -p storage/framework/sessions && \
    mkdir -p storage/framework/views && \
    mkdir -p storage/framework/cache && \
    chmod -R 775 storage/framework

RUN mkdir -p bootstrap/cache && \
   chmod -R 775 bootstrap/cache

EXPOSE 80

# Set permissions
RUN chown -R www-data:www-data /var/www \
    && chmod +x /usr/bin/supervisord

# Start Supervisor to manage NGINX and PHP-FPM
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf","php artisan storage:link && yes |  php artisan migrate --force | php artisan db:seed"]
