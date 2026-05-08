FROM php:8.2-fpm-alpine

# ─── System dependencies ───────────────────────────────────────────────────
RUN apk add --no-cache \
    bash \
    curl \
    git \
    zip \
    unzip \
    libxml2-dev \
    oniguruma-dev \
    icu-dev \
    icu-libs

# ─── PHP extensions ────────────────────────────────────────────────────────
# ctype, tokenizer, json ya vienen compilados en PHP 8.2 — no instalar
RUN docker-php-ext-configure intl \
 && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        xml \
        bcmath \
        intl \
        opcache

# ─── Composer ──────────────────────────────────────────────────────────────
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# ─── App ───────────────────────────────────────────────────────────────────
WORKDIR /var/www/backend

# Instalar dependencias primero (cacheo de capas)
COPY composer.json composer.lock ./
RUN composer install \
        --no-scripts \
        --no-autoloader \
        --no-interaction \
        --prefer-dist

# Copiar el resto del código
COPY . .

# Generar autoloader optimizado
RUN composer dump-autoload --optimize --classmap-authoritative

# ─── Permisos ──────────────────────────────────────────────────────────────
RUN mkdir -p storage/framework/{sessions,views,cache} \
        storage/logs \
        bootstrap/cache \
 && chown -R www-data:www-data /var/www/backend \
 && chmod -R 755 storage bootstrap/cache

# ─── PHP config ────────────────────────────────────────────────────────────
COPY docker/php.ini /usr/local/etc/php/conf.d/kaan.ini

# ─── Entrypoint ────────────────────────────────────────────────────────────
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["/entrypoint.sh"]
CMD ["php-fpm"]
