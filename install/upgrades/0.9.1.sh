#!/bin/sh

set -e

# protect all domains, not just new ones
. /usr/lib/alternc/basedir_prot.sh

servers="apache-ssl apache"
for server in $servers; do

includefile=/var/alternc/apacheconf/override_php.conf
. /usr/share/wwwconfig-common/apache-include_all.sh
[ "$status" = "uncomment" -o "$status" = "include" ] && restart="$server $restart"

done

. /usr/share/wwwconfig-common/restart.sh
