#!/bin/sh
set -eu

: "${PORT:=10000}"

case "$PORT" in
    *[!0-9]*|'')
        echo "PORT must be a numeric port." >&2
        exit 1
        ;;
esac

sed -i "s/__PORT__/${PORT}/g" /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf

exec apache2-foreground
