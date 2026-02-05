#!/bin/sh
set -e
# PhpMyAdmin configuration storage init: create phpmyadmin DB, pma user, and linked tables.
# Uses MYSQL_ROOT_PASSWORD and PMA_CONTROLPASS from env. Connects to host "db".

MYSQL_OPTS="-h db -u root -p${MYSQL_ROOT_PASSWORD}"

# Create database and pma user (idempotent)
mysql $MYSQL_OPTS -e "
  CREATE DATABASE IF NOT EXISTS phpmyadmin DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;
  CREATE USER IF NOT EXISTS 'pma'@'%' IDENTIFIED BY '${PMA_CONTROLPASS}';
  GRANT SELECT, INSERT, UPDATE, DELETE ON phpmyadmin.* TO 'pma'@'%';
  FLUSH PRIVILEGES;
"

# Import PhpMyAdmin linked-tables schema
mysql $MYSQL_OPTS phpmyadmin < /scripts/create_tables.sql

echo "PhpMyAdmin configuration storage ready."
exit 0
