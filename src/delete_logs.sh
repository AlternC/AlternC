#!/bin/bash

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
days=366
#parcourir tous les logs pour trouver ceux qui on plus de 1 ans et les deletes.
find "$ALTERNC_LOC/logs" -mtime +$days -exec rm '{}' \;
