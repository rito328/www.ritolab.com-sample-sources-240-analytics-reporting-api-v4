FROM php:8.1-cli

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
COPY config/php.ini /usr/local/etc/php/conf.d/app.ini

WORKDIR /usr/src/myapp
