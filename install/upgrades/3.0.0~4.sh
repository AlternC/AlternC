#!/bin/bash

. /etc/alternc/local.sh

# Add INOTIFY_UPDATE_DOMAIN var to local.sh
if [ -z "$INOTIFY_UPDATE_DOMAIN" ] ; then
/bin/echo -e '
# File to look at for forced launch of update_domain (use incron)
INOTIFY_UPDATE_DOMAIN="/var/run/alternc/inotify_update_domain.lock"
' >> /etc/alternc/local.sh
fi

mkdir -p /var/run/alternc && chown alterncpanel /var/run/alternc

