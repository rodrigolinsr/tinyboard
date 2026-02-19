#!/bin/sh
set -e

mkdir -p /var/www/html/var /var/www/html/logs
chown -R www-data:www-data /var/www/html/var /var/www/html/logs
