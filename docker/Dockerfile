FROM php:7.4-cli-buster

RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - && \
    apt-get install -y nodejs git zip && \
    npm install -g yarn
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod uga+x /usr/local/bin/install-php-extensions && sync && \
    install-php-extensions \
    zip \
    @composer \
    bcmath;

ARG USER_ID
ARG GROUP_ID

RUN if [ ${USER_ID:-0} -ne 0 ] && [ ${GROUP_ID:-0} -ne 0 ]; then \
    usermod -u ${USER_ID} www-data &&\
    groupmod -g ${GROUP_ID} www-data &&\
    chown ${USER_ID}:${GROUP_ID} -R /var/www \
    ;fi
