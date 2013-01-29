#!/bin/bash

# Launch update_domains.sh if $INOTIFY_UPDATE_DOMAIN exist

. /etc/alternc/local.sh

test -e "$INOTIFY_UPDATE_DOMAIN" && /usr/lib/alternc/update_domains.sh

