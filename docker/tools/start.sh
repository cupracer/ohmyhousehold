#!/bin/bash

#
# Copyright (c) 2023. Thomas Schulte <thomas@cupracer.de>
#
# Permission is hereby granted, free of charge, to any person obtaining a copy of this software
# and associated documentation files (the "Software"), to deal in the Software without restriction,
# including without limitation the rights to use, copy, modify, merge, publish, distribute,
# sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is
# furnished to do so, subject to the following conditions:
#
# The above copyright notice and this permission notice shall be included in all copies or
# substantial portions of the Software.
#
# THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO
# THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
# IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
# WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE
# OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
#

if [[ ! -z "$SSL_SUBJECT" ]]; then
	if [[ ! -f /certs/ssl-cert.key ]] && [[ ! -f /certs/ssl-cert.pem ]]; then
	  openssl req -x509 -nodes -days 820 -newkey rsa:2048 -keyout /certs/ssl-cert.key -out /certs/ssl-cert.pem -subj "${SSL_SUBJECT}"
	fi

	sed -i 's/##REWRITE_HTTPS##//g' /etc/apache2/sites-available/000-default.conf
	a2enmod ssl
	a2ensite default-ssl
fi

if [[ ! -z "$TZ" ]]; then
  printf "[PHP]\ndate.timezone = \"${TZ}\"\n" > /usr/local/etc/php/conf.d/tzone.ini
fi

case "${APP_ENV}" in
	dev)
		cp -a /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini
		test -f /usr/local/etc/php/conf.d/99-xdebug.ini.config && \
		    ln -sf /usr/local/etc/php/conf.d/99-xdebug.ini.config /usr/local/etc/php/conf.d/99-xdebug.ini
		;;
	*)
		cp -a /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini
		test -e /usr/local/etc/php/conf.d/99-xdebug.ini && rm -f /usr/local/etc/php/conf.d/99-xdebug.ini
		;;
esac

sed -i 's/^max_execution_time.*/max_execution_time = 120/' /usr/local/etc/php/php.ini

if [ -f /var/www/html/public/.htaccess ]; then
  sed -i '/##SYMFONY-APACHE-PACK##/ r /var/www/html/public/.htaccess' /etc/apache2/sites-available/000-default.conf
  sed -i '/##SYMFONY-APACHE-PACK##/ r /var/www/html/public/.htaccess' /etc/apache2/sites-available/default-ssl.conf
fi

#rm -f /var/www/html/.env.local
#test -z $APP_ENV || echo "APP_ENV=${APP_ENV}" >> /var/www/html/.env.local
#test -z $APP_BASEURL || echo "APP_BASEURL=${APP_BASEURL}" >> /var/www/html/.env.local
#test -z $DATABASE_URL || echo "DATABASE_URL=${DATABASE_URL}" >> /var/www/html/.env.local
#test -z $MAILER_DSN || echo "MAILER_DSN=${MAILER_DSN}" >> /var/www/html/.env.local
#test -z $APP_INITIAL_ADMIN_EMAIL || echo "APP_INITIAL_ADMIN_EMAIL=${APP_INITIAL_ADMIN_EMAIL}" >> /var/www/html/.env.local
#test -z $APP_MAILER_SENDER_ADDRESS || echo "APP_MAILER_SENDER_ADDRESS=${APP_MAILER_SENDER_ADDRESS}" >> /var/www/html/.env.local
#test -z $APP_MAILER_SENDER_NAME || echo "APP_MAILER_SENDER_NAME='${APP_MAILER_SENDER_NAME}'" >> /var/www/html/.env.local
#test -z $APP_MAILER_DEV_RECIPIENT || echo "APP_MAILER_DEV_RECIPIENT='${APP_MAILER_DEV_RECIPIENT}'" >> /var/www/html/.env.local
#test -z $APP_DATATABLES_USE_FIXED_COLUMNS || echo "APP_DATATABLES_USE_FIXED_COLUMNS='${APP_DATATABLES_USE_FIXED_COLUMNS}'" >> /var/www/html/.env.local

if [ "${APP_ENV}" == "prod" ]; then
  su www-data --shell=/bin/bash -c "php bin/console --no-interaction --env=${APP_ENV} doctrine:migrations:migrate"

# Disable opcache.preload for now, as it causes issues with PHP preloading; see Dockerfile for details
#  echo 'opcache.preload=/var/www/html/config/preload.php' > /usr/local/etc/php/conf.d/99-opcache-preload.ini
#  echo 'opcache.preload_user=www-data' >> /usr/local/etc/php/conf.d/99-opcache-preload.ini

else
  echo '***'
  echo '*** Remember to run "php bin/console doctrine:migrations:migrate" after DB structure changes ***'
  echo '***'
fi

su www-data --shell=/bin/bash -c "php bin/console --env=${APP_ENV} cache:clear"

RC=$?

if [ $RC -eq 0 ]; then
  apache2-foreground
else
  echo "Failed to prepare application. Exiting."
  exit $RC
fi
