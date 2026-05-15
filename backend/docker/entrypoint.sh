#!/bin/sh
set -e

php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
php bin/console doctrine:migrations:migrate --no-interaction --env=prod

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
