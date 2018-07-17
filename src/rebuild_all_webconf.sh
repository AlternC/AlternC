#!/bin/bash

# Get some vars
. /usr/lib/alternc/functions.sh

echo "This script will rebuild all web configuration and regenerate DNS."
echo "Use --force to skip confirmation"

if [ ! "$1" == "--force" ] ; then 
  read -n1 -p "Continue (y/n)? "
  [[ $REPLY = [yY] ]] ||  exit 1
fi

echo "++ Start rebuilding ++"

echo "Set flag to rebuild"
mysql_query "update sub_domaines set web_action = 'UPDATE' WHERE web_action != 'DELETE';"
mysql_query "update     domaines set dns_action = 'UPDATE' WHERE dns_action != 'DELETE';"

echo "Now launching update_domains to rebuild."
/usr/lib/alternc/update_domains.sh

echo "Finish."

