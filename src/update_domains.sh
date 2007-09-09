#!/bin/sh
#
# $Id: update_domaines.sh,v 1.31 2005/08/29 19:21:31 anarcat Exp $
# ----------------------------------------------------------------------
# AlternC - Web Hosting System
# Copyright (C) 2002 by the AlternC Development Team.
# http://alternc.org/
# ----------------------------------------------------------------------
# Based on:
# Valentin Lacambre's web hosting softwares: http://altern.org/
# ----------------------------------------------------------------------
# LICENSE
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License (GPL)
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# To read the license please visit http://www.gnu.org/copyleft/gpl.html
# ----------------------------------------------------------------------
# Original Author of file: Jerome Moinet for l'Autre Net - 14/12/2000
# Purpose of file: system level domain management
# ----------------------------------------------------------------------
#

PATH=/sbin:/bin:/usr/sbin:/usr/bin

set -e

umask 022

########################################################################
# Constants & Preliminary checks
#

CONFIG_FILE="/etc/alternc/local.sh"

DOMAIN_LOG_FILE="/var/log/alternc/update_domains.log"
DATA_ROOT="/var/alternc"

NAMED_TEMPLATE="/etc/bind/templates/named.template"
ZONE_TEMPLATE="/etc/bind/templates/zone.template"

ACTION_INSERT=0
ACTION_UPDATE=1
ACTION_DELETE=2
TYPE_LOCAL=0
TYPE_URL=1
TYPE_IP=2
TYPE_WEBMAIL=3
YES=1

if [ `id -u` -ne 0 ]; then
    echo "update_domains.sh must be launched as root"
    exit 1
fi

if [ ! -x "/usr/bin/get_account_by_domain" ]; then
    echo "Your AlternC installation is incorrect ! If you are using pre 0.9.4, "
    echo "you have to install alternc-admintools: "
    echo "    apt-get update ; apt-get install alternc-admintools"
    exit 1
fi

if [ ! -r "$CONFIG_FILE" ]; then
    echo "Can't access $CONFIG_FILE."
    exit 1
fi

. "$CONFIG_FILE"

if [ -z "$MYSQL_HOST" -o -z "$MYSQL_DATABASE" -o -z "$MYSQL_USER" -o \
     -z "$MYSQL_PASS" -o -z "$DEFAULT_MX" -o -z "$PUBLIC_IP" ]; then
    echo "Bad configuration. Please use:"
    echo "   dpkg-reconfigure alternc"
    exit 1
fi

if [ -f "$LOCK_FILE" ]; then
    echo "`date` $0: last cron unfinished or stale lock file." |
        tee -a "$DOMAIN_LOG_FILE" >&2
    exit 1
fi

NAMED_CONF_FILE="$DATA_ROOT/bind/automatic.conf"
ZONES_DIR="$DATA_ROOT/bind/zones"
APACHECONF_DIR="$DATA_ROOT/apacheconf"
OVERRIDE_PHP_FILE="$APACHECONF_DIR/override_php.conf"
WEBMAIL_DIR="$DATA_ROOT/bureau/admin/webmail"
LOCK_FILE="$DATA_ROOT/bureau/cron.lock"
HTTP_DNS="$DATA_ROOT/dns"
HTML_HOME="$DATA_ROOT/html"

MYSQL_SELECT="mysql -h${MYSQL_HOST} -u${MYSQL_USER}
                    -p${MYSQL_PASS} -Bs ${MYSQL_DATABASE}"
MYSQL_DELETE="mysql -h${MYSQL_HOST} -u${MYSQL_USER}
                    -p${MYSQL_PASS} ${MYSQL_DATABASE}"

########################################################################
# Functions
#
. /usr/lib/alternc/functions.sh

########################################################################
# Main
#

# Init

touch "$LOCK_FILE"
DOMAINS_TMP_FILE=`mktemp -t alternc.update_domains.XXXXXX`
HOSTS_TMP_FILE=`mktemp -t alternc.update_domains.XXXXXX`
RELOAD_ZONES_TMP_FILE=`mktemp -t alternc.update_domains.XXXXXX`

cleanup() {
    rm -f "$LOCK_FILE" "$DOMAINS_TMP_FILE" "$HOSTS_TMP_FILE"
    rm -f "$RELOAD_ZONES_TMP_FILE"
    exit 0
}

trap cleanup 0 1 2 15

# Query database

$MYSQL_SELECT <<EOF | tail -n '+1' > "$DOMAINS_TMP_FILE"
SELECT membres.login,
       domaines_standby.domaine,
       if (domaines_standby.mx = '', '@', domaines_standby.mx),
       domaines_standby.gesdns,
       domaines_standby.gesmx,
       domaines_standby.action
  FROM domaines_standby
       LEFT JOIN membres membres
               ON membres.uid = domaines_standby.compte
 ORDER BY domaines_standby.action
EOF

$MYSQL_SELECT <<EOF | tail -n '+1' > "$HOSTS_TMP_FILE"
SELECT membres.login,
       sub_domaines_standby.domaine,
       if (sub_domaines_standby.sub = '', '@', sub_domaines_standby.sub),
       if (sub_domaines_standby.valeur = '', 'NULL',
                                             sub_domaines_standby.valeur),
       sub_domaines_standby.type,
       sub_domaines_standby.action
  FROM sub_domaines_standby
       LEFT JOIN membres membres
               ON membres.uid = sub_domaines_standby.compte
 ORDER BY sub_domaines_standby.action desc
EOF

# Handle domain updates

if [ "`wc -l < $DOMAINS_TMP_FILE`" -gt 0 ]; then
    echo `date` >> $DOMAIN_LOG_FILE
    cat "$DOMAINS_TMP_FILE" >> $DOMAIN_LOG_FILE
fi

# We need to tweak the IFS as $MYSQL_SELECT use tabs to separate fields
OLD_IFS="$IFS"
IFS="	"
while read user domain mx are_we_dns are_we_mx action ; do
    IFS="$OLD_IFS" 

    DOMAIN_LETTER=`print_domain_letter "$domain"`
    USER_LETTER=`print_user_letter "$user"`

    case "$action" in
      $ACTION_INSERT)
        if [ "$are_we_dns" = "$YES" ] ; then
            init_zone "$domain"
        fi
        ;;

      $ACTION_UPDATE)
        if [ "$are_we_dns" = "$YES" ] ; then
            init_zone "$domain"
            change_mx "$domain" "$mx"
        else
            remove_zone "$domain"
        fi
        ;;

      $ACTION_DELETE)
        remove_zone "$domain"

        # remove symlinks
        rm -f "${HTTP_DNS}/${DOMAIN_LETTER}/"*".$domain"
        rm -f "${HTTP_DNS}/${DOMAIN_LETTER}/$domain"
        rm -rf "${HTTP_DNS}/redir/${DOMAIN_LETTER}/"*".$domain"
        rm -rf "${HTTP_DNS}/redir/${DOMAIN_LETTER}/$domain"
        ;;

      *)
        echo "Unknown action code: $action" >> "$DOMAIN_LOG_FILE"
        ;;
    esac

    IFS="	"
done < "$DOMAINS_TMP_FILE"
IFS="$OLD_IFS"

# Handle hosts update

if [ "`wc -l < $HOSTS_TMP_FILE`" -gt 0 ] ; then
    echo `date` >> $DOMAIN_LOG_FILE
    cat "$HOSTS_TMP_FILE" >> $DOMAIN_LOG_FILE
fi

OLD_IFS="$IFS"
IFS="	"
while read user domain host value type action; do
    IFS="$OLD_IFS"

    case "$action" in
      $ACTION_UPDATE | $ACTION_INSERT)
        add_host "$domain" "$type" "$host" "$value" "$user"
        ;;

      $ACTION_DELETE)
        delete_host "$domain" "$host"
        ;;

      *)
        echo "Unknown action code: $action" >> "$DOMAIN_LOG_FILE"
        ;;
    esac

    IFS="	"
done < "$HOSTS_TMP_FILE"
IFS="$OLD_IFS"

# Reload configuration for named and apache

RELOAD_ZONES=`cat "$RELOAD_ZONES_TMP_FILE"`
if [ ! -z "$RELOAD_ZONES" ]; then
    if [ "$RELOAD_ZONES" = "all" ]; then
        rndc reload || echo "Cannot reload bind" >> "$DOMAIN_LOG_FILE"
    else
        for zone in $RELOAD_ZONES; do
            rndc reload "$zone" || echo "Cannot reload bind for zone $zone" >> "$DOMAIN_LOG_FILE"
        done
    fi
    apachectl graceful > /dev/null || echo "Cannot restart apache" >> "$DOMAIN_LOG_FILE"
fi

# Cleanup

echo "DELETE FROM domaines_standby" | $MYSQL_DELETE 
echo "DELETE FROM sub_domaines_standby" | $MYSQL_DELETE 

# vim: et sw=4
