#!/bin/bash

# How many day do we keep the logs ?
DAYS=366

for CONFIG_FILE in \
      /etc/alternc/local.sh \
      /usr/lib/alternc/functions.sh
  do
    if [ ! -r "$CONFIG_FILE" ]; then
        echo "Can't access $CONFIG_FILE."
        exit 1
    fi
    . "$CONFIG_FILE"
done

# FIXME this var should be define in local.sh
ALTERNC_LOGS="$ALTERNC_LOC/logs"

nice 10 find "$ALTERNC_LOGS" -mtime +$DAYS -delete
