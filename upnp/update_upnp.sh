#!/bin/bash
# 
# This configures the upnp client for AlternC
# 

CONFIG_FILE="/etc/alternc/local.sh"

if [ ! -r "$CONFIG_FILE" ]; then
    echo "Can't access $CONFIG_FILE."
    exit 1
fi
. "$CONFIG_FILE"

# Some vars
umask 022
LOCK_FILE="/tmp/alternc-upnp.lock"

# Somes check before start operations
if [ `id -u` -ne 0 ]; then
    log_error "must be launched as root"
elif [ -z "$INTERNAL_IP" -o -z "$PUBLIC_IP" ]; then
    log_error "Bad configuration. Please use: dpkg-reconfigure alternc"
elif [ -f "$LOCK_FILE" ]; then
    process=$(ps f -p `cat "$LOCK_FILE"|tail -1`|tail -1|awk '{print $NF;}')
    if [ "$(basename $process)" = "$(basename "$0")" ] ; then
	log_error "last cron unfinished or stale lock file ($LOCK_FILE)."
    else
	rm "$LOCK_FILE"
    fi
fi

# We lock the application
echo $$ > "$LOCK_FILE"

# Check the status of the router 
upnpc -s
if [ "$?" != "0" ]
then
    
fi



rm -f "$LOCK_FILE"

exit 0

