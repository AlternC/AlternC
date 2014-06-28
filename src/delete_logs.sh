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

stop_if_jobs_locked

# ALTERNC_LOGS is from local.sh
find "$ALTERNC_LOGS" -mtime +$DAYS -delete
