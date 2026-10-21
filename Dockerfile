# syntax=docker/dockerfile:1

# ---- 1段目: 依存パッケージ(vendor)を取得する ----
# Composer本体はここだけに置き、最終イメージには持ち込まない
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

# ---- 2段目: 実行用イメージ ----
FROM php:8.3-fpm-alpine AS app
WORKDIR /var/www/html

# MySQLに繋ぐためのPHP拡張と、本番向けのphp.ini（エラー詳細を画面に出さない等）
# expose_php=Offは、レスポンスヘッダ(X-Powered-By)でPHPのバージョンを外部に漏らさないため
RUN docker-php-ext-install pdo_mysql opcache \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && echo "expose_php = Off" > "$PHP_INI_DIR/conf.d/zz-security.ini"

COPY --from=vendor /app/vendor ./vendor
COPY . .

# .dockerignoreで除外したディレクトリを作り直し、実行ユーザー(www-data)が書き込めるようにする
RUN mkdir -p storage/logs storage/framework/cache/data storage/framework/sessions storage/framework/views bootstrap/cache \
    && php artisan package:discover --ansi \
    && chown -R www-data:www-data storage bootstrap/cache

# rootでは動かさない
USER www-data

EXPOSE 9000
CMD ["php-fpm"]
