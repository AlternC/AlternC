#!/bin/bash

# Launch update_domains.sh if $INOTIFY_UPDATE_DOMAIN exist

. /etc/alternc/local.sh

LOGGER="/usr/bin/logger"

if [ -e "$INOTIFY_UPDATE_DOMAIN" ] ; then
    if [ -x "$LOGGER" ] ; then
         $LOGGER -t "ALTERNC Panel manual launch update_domain"
    fi
    /usr/lib/alternc/update_domains.sh
    rm "$INOTIFY_UPDATE_DOMAIN"
fi
