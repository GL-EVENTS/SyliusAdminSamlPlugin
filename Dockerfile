FROM node:20-alpine3.21 AS node
FROM composer:2.8 AS composer

FROM alpine:3.21

COPY --from=composer /usr/bin/composer /usr/bin/composer

COPY --from=node /usr/lib           /usr/lib
COPY --from=node /usr/local/share   /usr/local/share
COPY --from=node /usr/local/lib     /usr/local/lib
COPY --from=node /usr/local/include /usr/local/include
COPY --from=node /usr/local/bin     /usr/local/bin

RUN apk update --no-cache && apk add --no-cache \
    chromium-chromedriver \
    curl \
    supervisor \
    unzip \
    python3 \
    g++ \
    make \
    nginx \
    yarn \
    php83 \
    php83-apcu \
    php83-calendar \
    php83-common \
    php83-cli \
    php83-common \
    php83-ctype \
    php83-curl \
    php83-dom \
    php83-exif \
    php83-fileinfo \
    php83-fpm \
    php83-gd \
    php83-intl \
    php83-mbstring \
    php83-mysqli \
    php83-mysqlnd \
    php83-opcache \
    php83-pdo \
    php83-pdo_mysql \
    php83-pdo_pgsql \
    php83-pgsql \
    php83-phar \
    php83-session \
    php83-simplexml \
    php83-sodium \
    php83-sqlite3 \
    php83-tokenizer \
    php83-xml \
    php83-xmlwriter \
    php83-xsl \
    php83-zip

RUN rm -rf /var/lib/apk/lists/* /tmp/* /var/tmp/* /usr/share/doc/* /usr/share/man/* /var/cache/apk/*

RUN ln -sf /usr/sbin/php-fpm83 /usr/sbin/php-fpm \
    && ln -sf /usr/bin/php83 /usr/bin/php \
    && mkdir -p /run/php /var/log/php-fpm

RUN adduser -u 1000 -D -S -G www-data www-data

COPY .docker/php/supervisord.conf   /etc/supervisor/conf.d/supervisor.conf
COPY .docker/nginx/nginx.conf         /etc/nginx/nginx.conf
COPY .docker/php/php-fpm.conf       /etc/php83/php-fpm.conf
COPY .docker/php/php.ini            /etc/php83/php.ini

WORKDIR /app

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisor.conf"]
