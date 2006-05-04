#!/bin/sh
. /etc/alternc/local.sh
DATA_PART=`df ${ALTERNC_LOC} 2>/dev/null | awk '/^\// { print $1 }'`
/usr/sbin/setquota -r -g $1 $2 $2 0 0 $DATA_PART 2>/dev/null || echo "Group quota are not enabled on /var/alternc." >&2
