#! /bin/bash

# How long do we wait before compressing the log? Default: 2
LOGS_COMPRESS_DAYS=2

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

#Compress logs older than XX days
nice -n 10 find "$ALTERNC_LOGS" -type f -name '*.log' -mtime +$LOGS_COMPRESS_DAYS -exec gzip '{}' \;
