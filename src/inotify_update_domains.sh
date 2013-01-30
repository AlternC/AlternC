#!/bin/bash

# Launch update_domains.sh if $INOTIFY_UPDATE_DOMAIN exist

. /etc/alternc/local.sh

test -x /usr/bin/logger && /usr/bin/logger -t ALTERNC Panel manual launch update_domain

test -e "$INOTIFY_UPDATE_DOMAIN" && /usr/lib/alternc/update_domains.sh

