FROM php:8.2-apache AS tools

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
    yarn

RUN docker-php-ext-configure intl

RUN docker-php-ext-install  \
    bcmath \
    calendar \
    intl \
    pdo  \
    pdo_mysql

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" &&  \
    php -r "if (hash_file('sha384', 'composer-setup.php') === '55ce33d7678c5a611085589f1f3ddf8b3c52d662cd01d4ba75c0ee0459970c2200a51f492d557530c71c15d8dba01eae') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" && \
    php composer-setup.php --install-dir=/usr/local/bin --filename=composer &&  \
    php -r "unlink('composer-setup.php');"

RUN mkdir /var/www/.composer /var/www/.config /var/www/.cache /var/www/.local /var/www/.npm /var/www/.yarn /var/www/.symfony5 && \
    touch /var/www/.yarnrc && \
    chown www-data: /var/www/.composer /var/www/.config /var/www/.cache /var/www/.local /var/www/.npm /var/www/.yarn /var/www/.yarnrc /var/www/.symfony5

COPY docker/tools/setup.sh /setup.sh
RUN chmod 755 /setup.sh

###

FROM tools AS webserver

RUN docker-php-ext-enable opcache

RUN curl https://pecl.php.net/get/APCu -o /tmp/apcu.tar.gz \
        && mkdir -p /usr/src/php/ext/apcu \
        && tar xf /tmp/apcu.tar.gz -C /usr/src/php/ext/apcu --strip-components=1 \
        && docker-php-ext-configure apcu \
        && docker-php-ext-install apcu \
        && rm /tmp/apcu.tar.gz

RUN pecl install xdebug

COPY ./docker/tools/99-xdebug.ini /usr/local/etc/php/conf.d/99-xdebug.ini

RUN mkdir /certs

COPY docker/tools/apache/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY docker/tools/apache/default-ssl.conf /etc/apache2/sites-available/default-ssl.conf

RUN /usr/sbin/a2enmod rewrite \
	&& /usr/sbin/a2enmod ssl

COPY ./docker/tools/start.sh /start.sh
COPY ./docker/tools/www.sh /usr/local/sbin/www.sh

RUN chmod 750 /start.sh /usr/local/sbin/www.sh

CMD [ "/start.sh" ]

###

FROM webserver AS app

ARG APP_ENV
ENV APP_ENV=${APP_ENV:-prod}

RUN rm -f /usr/local/etc/php/conf.d/99-xdebug.ini

COPY . /var/www/html

RUN echo APP_ENV=$APP_ENV > /var/www/html/.env.local

RUN chown -R www-data: /var/www/html

RUN /setup.sh

HEALTHCHECK CMD curl -f http://localhost/ping || exit 1
