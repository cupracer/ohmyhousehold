#!/bin/bash

cd /var/www/html

CMD="COMPOSER_MEMORY_LIMIT=-1 composer install --no-interaction --optimize-autoloader"
echo " * ${CMD}"
su www-data --shell=/bin/bash -c "${CMD}"

CMD="yarn install"
echo " * ${CMD}"
su www-data --shell=/bin/bash -c "${CMD}"

CMD="yarn encore ${APP_ENV}"
echo " * ${CMD}"
su www-data --shell=/bin/bash -c "${CMD}"
