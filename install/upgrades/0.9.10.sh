#!/bin/sh

set -e

# We load local.sh
. /etc/alternc/local.sh

find ${ALTERNC_LOC}/dns -lname "${ALTERNC_LOC}/dns/redir/mail" -print -exec rm -f '{}' \; -exec ln -sf ${ALTERNC_LOC}/bureau/admin/webmail '{}' \;

rm -rf ${ALTERNC_LOC}/apacheconf
/usr/lib/alternc/basedir_prot.sh

# if apache exists we reload
if [ -x /etc/init.d/apache ] ; then
    invoke-rc.d apache reload
    invoke-rc.d apache-ssl reload
fi

# if apache2 exists we reload
if [ -x /etc/init.d/apache2 ] ; then
    invoke-rc.d apache2 force-reload
fi
