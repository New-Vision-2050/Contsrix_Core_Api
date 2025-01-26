# Stage 1: Builder
FROM composer:2.5 AS builder

# Set working directory
WORKDIR /var/www

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libonig-dev \
    libzip-dev \
    zip \
    curl

# Copy composer files
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Copy application code
COPY . .

# Generate optimized autoload files
RUN composer dump-autoload --optimize

# Stage 2: Production
FROM php:8.2-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
        bash \
        git \
        curl \
        libpng-dev \
        libonig-dev \
        libxml2-dev \
        libjpeg-turbo-dev \
        libfreetype6-dev \
        zip \
        libzip-dev \
        oniguruma-dev \
        supervisor \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
    gd  mbstring pdo_mysql zip opcache \
    intl exif pcntl bcmath \
    && pecl install redis \
    && docker-php-ext-enable redis

# Set working directory
WORKDIR /var/www

# Copy built application from builder stage
COPY --from=builder /var/www /var/www

# Set permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage

# Expose port 9000 and start PHP-FPM server
EXPOSE 9000
CMD ["php-fpm"]
