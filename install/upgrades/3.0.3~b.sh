#!/bin/bash
# Upgrading script to AlternC 3.0.3

CONFIG_FILE="/usr/lib/alternc/functions.sh"
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

. "$CONFIG_FILE"


## This part create TMP dir if don't exist
function create_tmp_dir() {
    read LOGIN || true
    while [ "$LOGIN" ]; do
        echo "Check tmp directory for $LOGIN"
        REP="$(get_html_path_by_name $LOGIN )/tmp/"
        [[ "$REP" != "/tmp/" ]] && ( test -d "$REP" || mkdir $REP )
        read LOGIN || true
    done
}

mysql_query "select login from membres order by login;" | create_tmp_dir
