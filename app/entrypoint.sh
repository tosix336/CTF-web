#!/bin/sh
set -eu

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_NAME="${DB_NAME:-vault}"
DB_USER="${DB_USER:-vault}"
DB_PASSWORD="${DB_PASSWORD:-vault_local_only}"

mkdir -p /run/app
printf '%s\n' 'vault-upload-9f2c' > /run/app/upload.key
chmod 0644 /run/app/upload.key

# GZCTF starts one challenge container, so bundle MariaDB for standalone use.
# The local Compose setup sets DB_HOST=db and continues using its separate DB service.
if [ "$DB_HOST" = "127.0.0.1" ] || [ "$DB_HOST" = "localhost" ]; then
    mkdir -p /run/mysqld
    chown -R mysql:mysql /run/mysqld

    if [ ! -d /var/lib/mysql/mysql ]; then
        mariadb-install-db --user=mysql --datadir=/var/lib/mysql >/dev/null
    fi

    mysqld_safe --datadir=/var/lib/mysql --bind-address=127.0.0.1 >/var/log/mysqld.log 2>&1 &

    ready=0
    for _ in $(seq 1 60); do
        if mariadb-admin ping --silent >/dev/null 2>&1; then
            ready=1
            break
        fi
        sleep 1
    done
    if [ "$ready" -ne 1 ]; then
        cat /var/log/mysqld.log
        exit 1
    fi

    mariadb -uroot <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\`;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';
CREATE USER IF NOT EXISTS '${DB_USER}'@'%' IDENTIFIED BY '${DB_PASSWORD}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'%';
FLUSH PRIVILEGES;
SQL

    if [ ! -f /var/lib/mysql/.vault_initialized ]; then
        mariadb -u"$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" < /docker-entrypoint-initdb.d/init.sql
        touch /var/lib/mysql/.vault_initialized
    fi
fi

exec apache2-foreground
