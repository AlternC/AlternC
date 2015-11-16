#!/bin/bash

# Launch do_actions.php if $INOTIFY_DO_ACTION exist

. /etc/alternc/local.sh

LOGGER="/usr/bin/logger"

if [ -e "$INOTIFY_DO_ACTION" ] ; then
    if [ -x "$LOGGER" ] ; then
         $LOGGER -t ALTERNC do_actions
    fi
    /usr/lib/alternc/do_actions.php
    rm "$INOTIFY_DO_ACTION"
fi
