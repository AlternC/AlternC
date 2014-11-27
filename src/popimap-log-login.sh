#!/bin/bash

# Script called by Dovecot when an user log in
# Log that the user just log in

# /!\ Script is launched by dovecot with root permissions /!\

# Do not use parameters, Dovecot give environnment vars
# The only parameters is the expected binary server

ALTERNC_CONFIG_FILE="/usr/lib/alternc/functions.sh"
if [ ! -r "$ALTERNC_CONFIG_FILE" ]; then
    echo "Can't access $ALTERNC_CONFIG_FILE."
    exit 1
fi
. "$ALTERNC_CONFIG_FILE"

addr=$(echo $USER | sed 's/@.*//')
dom=$(echo $USER | sed 's/^.*@//')

mysql_query "update address a, domaines d, mailbox m set m.lastlogin=now() where a.domain_id=d.id and m.address_id=a.id and a.address='$addr' and d.domaine='$dom';"

# Now launch the expected binary server
exec "$@"
