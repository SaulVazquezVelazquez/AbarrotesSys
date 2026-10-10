#!/bin/sh
set -eu

PORT="${PORT:-10000}"
sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:10000>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

mkdir -p writable/cache writable/debugbar writable/logs writable/session writable/uploads/clientes writable/uploads/comprobantes
chown -R www-data:www-data writable

exec apache2-foreground
