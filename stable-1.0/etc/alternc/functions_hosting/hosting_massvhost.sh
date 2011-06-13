#!/bin/bash

ACTION=$1
# $2 is the type
DOMAIN=$3
TARGET=$4

# Load some librairies
. /etc/alternc/local.sh
. /usr/lib/alternc/functions.sh

# To not be case-sensitive
ACTION="`echo $ACTION|tr '[:upper:]' '[:lower:]'`"
DOMAIN="`echo $DOMAIN|tr '[:upper:]' '[:lower:]'`"

if [ -z $ACTION ] || [ -z $DOMAIN ] ; then
  echo "Need at least 2 parameters ( action - fqdn )"
fi

YOP="$ALTERNC_LOC/dns/$(print_domain_letter $DOMAIN)/$DOMAIN"

case $ACTION in
"disable"|"delete")
  if [ ! -e "$YOP" ] ; then
    echo "Link do not exist. Nothing done"
    exit 15
  fi
  if [ ! -L "$YOP" ] ; then
    echo "Seem not to be a link ($YOP). Abord"
    exit 16
  fi
  rm -f "$YOP"
  ;;
"enable"|"create")
  if [ -z "$TARGET" ] ; then
    echo "Parameters target $TARGET missing"
    exit 13
  fi
  USER=$(get_account_by_domain "$DOMAIN")
  if [ -z $USER ] ; then
    echo "Unable to find account of $DOMAIN"
    exit 17
  fi
  TARGET="$ALTERNC_LOC/html/$(print_user_letter $USER)/$USER/$TARGET"
  if [ ! -d "$TARGET" ] ; then
    echo "Directory $TARGET missing"
    exit 14
  fi
  ln -snf "$TARGET" "$YOP"
  ;;
*)
  echo Error : $ACTION not an recognised action
  exit 11
  ;;
esac

