#!/bin/bash

# Awstats configuration files regeneration by Axel
# Delete old stats configuration files, and
# regenerate them from the database with the new template!

CONFIG_FILE="/usr/lib/alternc/functions.sh"
TEMPLATE="/etc/alternc/templates/awstats/awstats.template.conf"
DIR_TARGET="/etc/awstats"
TMP_FILE=$(mktemp "/tmp/awstats_conf.XXXXXX")

if [ ! -r "$CONFIG_FILE" ]; then
    echo "Can't access $CONFIG_FILE."
    exit 1
fi
. "$CONFIG_FILE"

# Delete old stats configuration files
rm "$DIR_TARGET"/awstats.*.conf

$MYSQL_DO "SELECT id, hostname, public, hostaliases FROM aws;" | while read id hostname public hostaliases ; do
    cp "$TEMPLATE" "$TMP_FILE"
    users=""
    $MYSQL_DO "SELECT login FROM aws_access WHERE id=$id;" | (while read login ; do
        users="$users$login "
    done

    # Put the good value in the conf file
    sed -i \
    -e "s#%%HOSTNAME%%#$hostname#g" \
    -e "s#%%PUBLIC%%#$public#g" \
    -e "s#%%HOSTALIASES%%#$hostaliases#g" \
    -e "s#%%USERS%%#$users#g" \
    "$TMP_FILE")

    # Set conf file with good rights
    # And put it in prod
    cp -f "$TMP_FILE" "$DIR_TARGET/awstats.$hostname.conf"
    chmod 644 "$DIR_TARGET/awstats.$hostname.conf"
    chown alterncpanel:alterncpanel "$DIR_TARGET/awstats.$hostname.conf"
done

# Remove temporary file
rm "$TMP_FILE"
