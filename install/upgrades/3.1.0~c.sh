#!/bin/bash
# Upgrading script to AlternC 3.0.3

CONFIG_FILE="/usr/lib/alternc/functions.sh"
PATH=/sbin:/bin:/usr/sbin:/usr/bin

umask 022

if [ ! -r "$CONFIG_FILE" ]; then
    echo "Can't access $CONFIG_FILE."
    exit 1
fi

if [ `id -u` -ne 0 ]; then
    echo "$0 must be launched as root"
    exit 1
fi

. "$CONFIG_FILE"


#### Migrate zone file of AlternC 1.0 from old directory 

# If no old directory, nothing to do here
test -d /var/alternc/bind/zones/ || exit 0

# Copy file but do not overwrite them
cp --no-clobber /var/alternc/bind/zones/* /var/lib/alternc/bind/zones/

# No need to regenerate zone, we are launched by upgrade_check,
# launched by alternc.install, and alternc.install regenerate everything
# when it end
