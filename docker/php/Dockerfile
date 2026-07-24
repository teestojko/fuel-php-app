FROM composer:2.8 AS composer

FROM php:8.3-fpm-bookworm AS base

# FuelPHP、MySQL接続、UTF-8ロケールに必要な最小限のライブラリを導入します。
RUN apt-get update \
    && apt-get install -y --no-install-recommends libicu-dev libzip-dev locales unzip \
    && sed -i 's/^# en_US.UTF-8 UTF-8/en_US.UTF-8 UTF-8/' /etc/locale.gen \
    && locale-gen \
    && docker-php-ext-install intl opcache pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

# PHPとFuelPHPが共通して使用するUTF-8ロケールです。
ENV LANG=en_US.UTF-8
ENV LC_ALL=en_US.UTF-8

# Composerは公式イメージから実行ファイルだけを取得します。
COPY --from=composer /usr/bin/composer /usr/local/bin/composer
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-application.ini
COPY docker/php/php-fpm-healthcheck /usr/local/bin/php-fpm-healthcheck
RUN chmod 0555 /usr/local/bin/php-fpm-healthcheck
WORKDIR /var/www/backend

FROM base AS development
USER www-data

FROM base AS production
COPY --chown=www-data:www-data backend/composer.json backend/composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --classmap-authoritative
COPY --chown=www-data:www-data backend /var/www/backend
USER www-data
