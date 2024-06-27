#!/bin/bash

CONFIG_FILE="/usr/lib/alternc/functions.sh"
PATH=/sbin:/bin:/usr/sbin:/usr/bin

umask 022

if [ ! -r "$CONFIG_FILE" ]; then
    echo "Can't access $CONFIG_FILE."
    exit 1
fi

if [ "$(id -u)" -ne 0 ]; then
    echo "$0 script must be launched as root"
    exit 1
fi

# shellcheck source=src/functions.sh
. "$CONFIG_FILE"


#Force column as NULLABLE if default value is NULL
sql='select CONCAT("ALTER TABLE ", table_schema, ".", table_name, " MODIFY ", column_name, " ", column_type, ";" ) FROM information_schema.columns WHERE table_schema = "alternc" AND column_default IS NULL order by column_name;'
queries=$(mysql_query "${sql}")


for query in "${queries[@]}"; do
    mysql_query "${query}"
done