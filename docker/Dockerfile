FROM php:7.4-cli-alpine

RUN apk add --no-cache --update nodejs npm yarn zip zlib-dev git;
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod uga+x /usr/local/bin/install-php-extensions && sync && \
    install-php-extensions \
    zip \
    @composer \
    bcmath;

ARG USER_ID
ARG GROUP_ID

RUN if [ ${USER_ID:-0} -ne 0 ] && [ ${GROUP_ID:-0} -ne 0 ]; then \
    deluser www-data &&\
    if getent group www-data ; then delgroup www-data; fi &&\
    addgroup -g ${GROUP_ID} -S www-data; \
    adduser -D -u ${USER_ID} -S -G www-data www-data \
    ;fi
