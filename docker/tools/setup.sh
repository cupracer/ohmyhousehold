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

cd /var/www/html

CMD="COMPOSER_MEMORY_LIMIT=-1 composer install --no-interaction --optimize-autoloader"
echo " * ${CMD}"
su www-data --shell=/bin/bash -c "${CMD}"

CMD="php bin/console assets:install --no-interaction"
echo " * ${CMD}"
su www-data --shell=/bin/bash -c "${CMD}"

CMD="yarn install"
echo " * ${CMD}"
su www-data --shell=/bin/bash -c "${CMD}"

CMD="yarn encore ${APP_ENV}"
echo " * ${CMD}"
su www-data --shell=/bin/bash -c "${CMD}"
