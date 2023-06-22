# TODO: check version regularly
FROM php:8.2-apache as common_base

# Locale settings to avoid issues with Symfony and keyboard input
RUN apt-get update && \
    apt-get install -y locales && \
    apt-get clean && \
    localedef -i C -f UTF-8 C.UTF-8
ENV LANG C.UTF-8
ENV LANGUAGE C.UTF-8
ENV LC_ALL C.UTF-8

ARG APP_ENV

RUN mkdir /certs

COPY docker/tools/apache/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY docker/tools/apache/default-ssl.conf /etc/apache2/sites-available/default-ssl.conf

RUN /usr/sbin/a2enmod rewrite \
    && /usr/sbin/a2ensite 000-default

COPY ./docker/tools/start.sh /start.sh
COPY ./docker/tools/www.sh /usr/local/sbin/www.sh

RUN chmod 750 /start.sh /usr/local/sbin/www.sh

###

FROM common_base AS dev_build

# Symfony CLI repo
RUN curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.deb.sh' | bash

# NodeJS + Yarn repo
RUN curl -fsSL https://deb.nodesource.com/setup_lts.x | bash - && \
    curl -sL https://dl.yarnpkg.com/debian/pubkey.gpg | gpg --dearmor | tee /usr/share/keyrings/yarnkey.gpg >/dev/null && \
    echo "deb [signed-by=/usr/share/keyrings/yarnkey.gpg] https://dl.yarnpkg.com/debian stable main" | tee /etc/apt/sources.list.d/yarn.list

RUN apt-get update && apt-get install -y  \
    apt-transport-https \
    apt-utils \
    git-core  \
    gnupg \
    libicu-dev \
    locales-all \
    lsb-release \
    symfony-cli \
    unzip \
    vim \
    yarn && \
    apt-get clean

RUN docker-php-ext-configure intl

RUN docker-php-ext-install  \
    bcmath \
    calendar \
    intl \
    pdo  \
    pdo_mysql

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" &&  \
    php -r "if (hash_file('sha384', 'composer-setup.php') === 'e21205b207c3ff031906575712edab6f13eb0b361f2085f1f1237b7126d785e826a450292b6cfd1d64d92e6563bbde02') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" && \
    php composer-setup.php --install-dir=/usr/local/bin --filename=composer &&  \
    php -r "unlink('composer-setup.php');"

RUN mkdir /var/www/.composer /var/www/.config /var/www/.cache /var/www/.local /var/www/.npm /var/www/.yarn /var/www/.symfony5 && \
    touch /var/www/.yarnrc && \
    chown www-data: /var/www/.composer /var/www/.config /var/www/.cache /var/www/.local /var/www/.npm /var/www/.yarn /var/www/.yarnrc /var/www/.symfony5

COPY docker/tools/setup.sh /setup.sh

RUN chmod 755 /setup.sh

# Disabled due to preloading failure with opcache and omines/datatables-bundle.
# Also disabled in start.sh script. Failure is:
#
# PHP Warning:  Can't preload unlinked class
# Omines\\DataTablesBundle\\Adapter\\Doctrine\\ORM\\QueryBuilderProcessorInterface@anonymous:
# Unknown interface Omines\\DataTablesBundle\\Adapter\\Doctrine\\ORM\\QueryBuilderProcessorInterface in
# /var/www/html/vendor/omines/datatables-bundle/src/Adapter/Doctrine/ORMAdapter.php on line 338
# RUN docker-php-ext-enable opcache

RUN curl https://pecl.php.net/get/APCu -o /tmp/apcu.tar.gz \
        && mkdir -p /usr/src/php/ext/apcu \
        && tar xf /tmp/apcu.tar.gz -C /usr/src/php/ext/apcu --strip-components=1 \
        && docker-php-ext-configure apcu \
        && docker-php-ext-install apcu \
        && rm /tmp/apcu.tar.gz

# TODO: check version regularly
RUN pecl install xdebug-3.2.0

# TODO: check version regularly
RUN pecl install redis-5.3.7

COPY ./docker/tools/99-xdebug.ini.config /usr/local/etc/php/conf.d/99-xdebug.ini.config
COPY ./docker/tools/99-redis.ini /usr/local/etc/php/conf.d/99-redis.ini

CMD [ "/start.sh" ]

###

FROM dev_build AS thick_app

ENV APP_ENV=${APP_ENV:-prod}

COPY --chown=www-data:www-data . /var/www/html

RUN echo APP_ENV=$APP_ENV > /var/www/html/.env.local

RUN /setup.sh

# clean up node_modules source packages
RUN rm -rf /var/www/html/node_modules

###

FROM common_base as production_ready

ENV APP_ENV=${APP_ENV:-prod}

COPY --from=thick_app /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions
COPY --from=thick_app /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d
COPY --from=thick_app --chown=www-data:www-data /var/www/html/ /var/www/html

RUN echo APP_ENV=$APP_ENV > /var/www/html/.env.local

CMD [ "/start.sh" ]
