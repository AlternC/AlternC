#! /bin/bash

# How long do we wait before compressing the log ? Default: 2
DAYS=2

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

#FIXME: should be define in local.sh
ALTERNC_LOGS="$ALTERNC_LOC/logs"

#Compress logs older than XX days
nice -n 10 find "$ALTERNC_LOGS" -not -name '*.gz' -mtime +$DAYS -exec gzip '{}' \;

