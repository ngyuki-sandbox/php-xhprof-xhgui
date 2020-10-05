FROM php:7.4-fpm-alpine

# RUN mkdir -p /tmp/tideways_xhprof &&\
#     curl -fsSL https://github.com/tideways/php-xhprof-extension/archive/v5.0.2.tar.gz |\
#         tar xzf - --strip-components=1 -C /tmp/tideways_xhprof &&\
#     docker-php-ext-install /tmp/tideways_xhprof &&\
#     rm -fr /tmp/tideways_xhprof

RUN apk add --no-cache --virtual .build-deps autoconf gcc g++ make &&\
    pecl install xhprof &&\
    apk del .build-deps &&\
    rm -fr /tmp/pear &&\
    docker-php-ext-enable xhprof

COPY --from=composer /usr/bin/composer /usr/bin/composer

RUN docker-php-ext-enable opcache


