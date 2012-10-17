#!/bin/bash

# Script called by Dovecot when an user log in
# Log that the user just log in

# /!\ Script is launched by dovecot with root permissions /!\

# Do not use parameters, Dovecot give environnment vars
# The only parameters is the expected binary server

CONFIG_FILE="/usr/lib/alternc/functions.sh"
if [ ! -r "$CONFIG_FILE" ]; then
    echo "Can't access $CONFIG_FILE."
    exit 1
fi
. "$CONFIG_FILE"

mysql_query "update address a, domaines d, mailbox m set m.lastlogin=now() where a.domain_id=d.id and m.address_id=a.id and concat_ws('@',a.address,d.domaine) = '$USER';"

# Now launch the expected binary server
exec "$@"
