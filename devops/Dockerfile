# Base image فيها nginx + php 8.2
FROM mabou7agar/custom-nginx-php8-2

# Install zip extension (مرة واحدة ومع caching كويس)
RUN apt-get update && apt-get install -y \
        libzip-dev zip unzip \
    && docker-php-ext-install zip \
    && rm -rf /var/lib/apt/lists/*

ARG CACHEBUST=1

# نشتغل من هنا
WORKDIR /var/www

# 1) ننسخ ملفات Composer بس الأول عشان نستفيد من الـ cache
COPY composer.json composer.lock ./

# 2) نثبت الـ dependencies
RUN composer install --no-scripts

# 3) بعد كده ننسخ باقي المشروع
COPY . .

# Nginx / Supervisor configs
COPY devops/nginx/nginx.conf /etc/nginx/nginx.conf
COPY devops/nginx/sites-enabled/default.conf /etc/nginx/sites-enabled/default.conf
COPY devops/supervisord.conf /etc/supervisord.conf

# PHP ini
RUN echo "upload_max_filesize=500M" > /usr/local/etc/php/conf.d/uploads.ini && \
    echo "post_max_size=500M" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "memory_limit=512M" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "max_execution_time=300" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "max_input_time=300" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "output_buffering=Off" >> /usr/local/etc/php/conf.d/uploads.ini

# Laravel folders & permissions
RUN mkdir -p storage/framework/sessions \
    storage/framework/views \
    storage/framework/cache \
    storage/logs \
    bootstrap/cache && \
    chmod -R 775 storage framework storage/logs bootstrap/cache

HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
    CMD curl -f http://localhost/health || exit 1

RUN chown -R www-data:www-data /var/www \
    && chmod +x /usr/bin/supervisord \
    && chmod +x devops/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["devops/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
