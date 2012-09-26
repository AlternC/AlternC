#!/bin/sh

CONFIG_FILE="/etc/alternc/local.sh"

PATH=/sbin:/bin:/usr/sbin:/usr/bin

umask 022

if [ ! -r "$CONFIG_FILE" ]; then
    echo "Can't access $CONFIG_FILE."
    exit 1
fi

if [ `id -u` -ne 0 ]; then
    echo "fixperms.sh must be launched as root"
    exit 1
fi

fix_mail() {
    read LOGIN GID || true
    while [ "$LOGIN" ]; do
        INITIALE=`echo $LOGIN |cut -c1`
        REP="$ALTERNC_LOC/mail/$INITIALE/$LOGIN/"
        chown --recursive $GID:vmail "$REP"
}

query="select user,userdb_gid from dovecot_view"
mysql --defaults-file=/etc/alternc/my.cnf --skip-column-names -B -e "$query" |fix_mail




