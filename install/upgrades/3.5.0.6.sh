#!/bin/sh

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