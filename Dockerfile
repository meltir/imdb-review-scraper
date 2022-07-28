FROM php:8.1-cli-alpine

LABEL maintainer="Lukasz Andrzejak"
LABEL description="composer packager image"
ENV TZ="Europe/London"

ENTRYPOINT ["php"]
CMD ["-S","localhost:8080"]

RUN mkdir -p /root/.composer/vendor/bin
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV PATH="${PATH}:/root/.composer/vendor/bin"


RUN apk add --virtual .phpize-deps autoconf g++ make \
    && pecl install xdebug && docker-php-ext-enable xdebug \
    && apk del .phpize-deps

RUN apk add icu-dev icu \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl \
    && apk del icu-dev


RUN wget https://github.com/infection/infection/releases/download/0.26.13/infection.phar -O /root/.composer/vendor/bin/infection.phar \
    && chmod +x /root/.composer/vendor/bin/infection.phar

WORKDIR /var/www/html

COPY . .

RUN set -x \
  && composer install
