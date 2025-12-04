FROM robotshopdevacr.azurecr.io/baseimages/composer:2.9.1-internal AS composer

FROM robotshopdevacr.azurecr.io/baseimages/php:8.3-cli-alpine-internal AS build

WORKDIR /app

RUN apk update && apk add --no-cache \
    curl-dev \
    mysql-client \
    libcurl \
    unzip \
    $PHPIZE_DEPS

RUN docker-php-ext-install pdo pdo_mysql

COPY --from=composer /usr/bin/composer /usr/bin/composer

COPY html/composer.json html/composer.lock* /app/

RUN composer install --no-dev --prefer-dist --no-progress --no-interaction

FROM robotshopdevacr.azurecr.io/baseimages/php:8.3-cli-alpine-internal AS runtime

WORKDIR /app

COPY html/ /app

COPY --from=build /app/vendor /app/vendor

CMD ["sh", "-c", "php -S 0.0.0.0:8626 index.php"]