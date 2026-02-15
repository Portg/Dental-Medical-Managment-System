#!/bin/sh
set -e

ROLE=${CONTAINER_ROLE:-app}

case "$ROLE" in
  app)
    cron
    exec php-fpm
    ;;
  worker)
    exec php /var/www/html/artisan queue:work redis \
      --sleep=3 --tries=3 --max-time=3600
    ;;
  scheduler)
    exec cron -f
    ;;
  *)
    echo "Unknown role: $ROLE" >&2
    exit 1
    ;;
esac
