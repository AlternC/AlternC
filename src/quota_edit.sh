#!/bin/sh
. /etc/alternc/local.sh
/usr/sbin/setquota -r -g $1 $2 $2 0 0 $ALTERNC_LOC 2>/dev/null || echo "Group quota are not enabled on /var/alternc." >&2
