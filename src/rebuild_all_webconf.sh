#!/bin/bash

# Get some vars
. /usr/lib/alternc/functions_hosting.sh

if [ -z "$VHOST_DIR" ] ; then
  echo "Problem: No VHOST_DIR var"
  exit 2
fi

echo "This script empty the $VHOST_DIR directory"
echo "and rebuild all web configuration."
echo ""
echo "Only files in $VHOST_MANUALCONF will be preserved."
echo "Use --force to skip confirmation"
echo ""

if [ ! "$1" == "--force" ] ; then 
  read -n1 -p "Continue (y/n)? "
  [[ $REPLY = [yY] ]] ||  exit 1
fi

echo ""
echo "++ Start rebuilding ++"

echo "Delete old configuration"
# [a-z_] for old storage schema (1.0) 
for i in 0 1 2 3 4 5 6 7 8 9 a b c d e f g h i j k l m n o p q r s t u v w x y z _ ; do
  test -d "$VHOST_DIR/$i" && rm -rf "$VHOST_DIR/$i/"
done
test -f "$VHOST_FILE" && rm -f "$VHOST_FILE"
echo "Deleting complete"

echo "Set flag to rebuild"
mysql_query "update sub_domaines set web_action = 'UPDATE';"

echo "Launch update_domains to rebuild."
/usr/lib/alternc/update_domains.sh

echo "Finish."

