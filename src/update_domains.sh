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

print_domain_letter() {
    local domain="$1"

    local letter=`echo "$domain" | awk '{z=split($NF, a, ".") ; print substr(a[z-1], 1, 1)}'`
    if [ -z "$letter" ]; then
      letter="_"
    fi
    echo $letter
}

print_user_letter() {
    local user="$1"

    echo "$user" | awk '{print substr($1, 1, 1)}'
}

add_to_php_override() {
    local fqdn="$1"

    /usr/lib/alternc/basedir_prot.sh "$fqdn" >> "$DOMAIN_LOG_FILE"
}

remove_php_override() {
    local fqdn="$1"
    local letter=`print_domain_letter $fqdn`

    sed -i "/$fqdn/d" $APACHECONF_DIR/override_php.conf
    rm -f $APACHECONF_DIR/$letter/$fqdn
}

add_to_named_reload() {
    local domain="$1"
    local escaped_domain=`echo "$domain" | sed -e 's/\./\\\./g'`

    if [ "domain" = "all" ] || grep -q "^all$" "$RELOAD_ZONES_TMP_FILE"; then
        echo "all" > "$RELOAD_ZONES_TMP_FILE"
    else
        if ! grep -q "^${escaped_domain}$" "$RELOAD_ZONES_TMP_FILE"; then
            echo "$domain" >> "$RELOAD_ZONES_TMP_FILE"
        fi
    fi
}

# we assume that the serial line contains the "serial string", eg.:
#                 2005012703      ; serial
#
# returns 1 if file isn't readable
# returns 2 if we can't find the serial number
# returns 3 if a tempfile can't be created
increment_serial() {
    local domain="$1"
    local zone_file="$ZONES_DIR/$domain"
    local current_serial
    local new_serial
    local date
    local revision
    local today

    if [ ! -f "$zone_file" ]; then
        return 1
    fi

    # the assumption is here
    current_serial=`awk '/^..*serial/ {print $1}' < "$zone_file"` || return 2
    if [ -z "$current_serial" ]; then
        return 2
    fi

    date=`echo $current_serial | cut -c1-8`
    revision=`echo $current_serial | sed s/"${date}0\?"/""/g`
    today=`date +%Y%m%d`
    # increment the serial number only if the date hasn't changed
    if [ "$date" = "$today" ] ; then
        revision=$(($revision + 1))
    else
        revision=1
        date=$today
    fi
    new_serial="$date`printf '%.2d' $revision`"

    # replace serial number
    cp -a -f "$zone_file" "$zone_file.$$"
    awk -v "NEW_SERIAL=$new_serial" \
        '{if ($3 == "serial")
             print "		"NEW_SERIAL "	; serial"
          else
             print $0}' < "$zone_file" > "$zone_file.$$"
    mv -f "$zone_file.$$" "$zone_file"

    add_to_named_reload "$domain"

    return 0
}

change_host_ip() {
    local domain="$1"
    local zone_file="$ZONES_DIR/$domain"
    local ip="$2"
    local host="$3"
    local pattern
    local a_line

    if [ -z "$host" ]; then
        host="@"
    fi
    a_line="$host 	IN	A 	$ip"
    pattern="^$host[[:space:]]*IN[[:space:]]*A[[:space:]]*.*\$"
    if [ ! -f "$zone_file" ]; then
        echo "Should change $host.$domain, but can't find $zone_file."
        return 1
    fi 
    if grep -q "$pattern" "$zone_file"; then
        cp -a -f "$zone_file" "$zone_file.$$"
        sed "s/$pattern/$a_line/" < "$zone_file" > "$zone_file.$$"
        mv "$zone_file.$$" "$zone_file"
    else
        echo "$a_line" >> "$zone_file"
    fi
    add_to_named_reload "$domain"
}

add_host() {
    local domain="$1"
    local host_type="$2"
    local host="$3"
    local value="$4"
    local user="$5"
    local domain_letter=`print_domain_letter "$domain"`
    local user_letter=`print_user_letter "$user"`
    local ip
    local fqdn
    local vhost_directory
	
    delete_host "$domain" "$host"

    if [ "$host" = "@" -o -z "$host" ]; then
        FQDN="$domain"
    else
        FQDN="$host.$domain"
    fi
    if [ "$host_type" != "$TYPE_IP" ]; then
        add_to_php_override "$FQDN"
    fi

    if [ "$host_type" = "$TYPE_IP" ]; then
       ip="$value"
    else
       ip="$PUBLIC_IP"
    fi
    if [ "$host" = "@" -o -z "$host" ]; then
        change_host_ip "$domain" "$ip" || true
        fqdn="$domain"
    else
        change_host_ip "$domain" "$ip" "$host" || true
        fqdn="${host}.${domain}"
    fi

    vhost_directory="${HTTP_DNS}/${domain_letter}/${fqdn}"
    htaccess_directory="${HTTP_DNS}/redir/${domain_letter}/${fqdn}"

    case "$host_type" in
      $TYPE_LOCAL)
        ln -snf "${HTML_HOME}/${user_letter}/${user}${value}" \
                "$vhost_directory"
        ;;

      $TYPE_WEBMAIL)
        ln -snf "${WEBMAIL_DIR}" "$vhost_directory"
        ;;

      $TYPE_URL)
        mkdir -p "$htaccess_directory"
        (echo "RewriteEngine on"
         echo "RewriteRule (.*) ${value}/\$1 [R,L]"
        ) > "$htaccess_directory/.htaccess"
        ln -snf "$htaccess_directory" "$vhost_directory"
        ;;
	
      $TYPE_IP)
        rm -f "$vhost_directory"
        rm -rf "$htaccess_directory/.htaccess"
        ;;

      *)
        echo "Unknow type code: $type" >> "$DOMAIN_LOG_FILE"
        ;;
    esac
}

delete_host() {
    local domain="$1"
    local host="$2"
    local domain_letter=`print_domain_letter "$domain"`
    local fqdn
    local escaped_host
    local escaped_fqdn
    
    if [ "$host" = "@" -o -z "$host" ]; then
        fqdn="$domain"
        escaped_host=""
    else
        fqdn="$host.$domain"
        escaped_host=`echo "$host" | sed 's/\([\*|\.]\)/\\\\\1/g'`
    fi

    if [ -f "$ZONES_DIR/$domain" ] ; then
        cp -a -f "$ZONES_DIR/$domain" "$ZONES_DIR/$domain.$$"
        sed -e "/^$escaped_host[[:space:]]*IN[[:space:]]*A[[:space:]]/d" \
            < "$ZONES_DIR/$domain" > "$ZONES_DIR/$domain.$$"
        mv "$ZONES_DIR/$domain.$$" "$ZONES_DIR/$domain"
        increment_serial "$domain"
        add_to_named_reload "$domain"
    fi

    rm -f "$APACHECONF_DIR/$domain_letter/$fqdn"

    escaped_fqdn=`echo "$fqdn" | sed 's/\([\*|\.]\)/\\\\\1/g'`

    cp -a -f "$OVERRIDE_PHP_FILE" "$OVERRIDE_PHP_FILE.$$"
    sed -e "/\/${escaped_fqdn}\$/d" \
        < "$OVERRIDE_PHP_FILE" > "$OVERRIDE_PHP_FILE.$$"
    mv "$OVERRIDE_PHP_FILE.$$" "$OVERRIDE_PHP_FILE"

    rm -f "$HTTP_DNS/$domain_letter/$fqdn"
    rm -rf "$HTTP_DNS/redir/$domain_letter/$fqdn"
}


init_zone() {
    local domain="$1"
    local escaped_domain=`echo "$domain" | sed -e 's/\./\\\./g'`
    local zone_file="$ZONES_DIR/$domain"
    local serial

    if [ ! -f "$zone_file" ]; then
        serial=`date +%Y%m%d`00
        sed -e "s/@@DOMAINE@@/$domain/g;s/@@SERIAL@@/$serial/g" \
            < "$ZONE_TEMPLATE" > "$zone_file"
        chgrp bind "$zone_file"
        chmod 640  "$zone_file"
    fi
    if ! grep -q "\"$escaped_domain\"" "$NAMED_CONF_FILE"; then
        cp -a -f "$NAMED_CONF_FILE" "$NAMED_CONF_FILE".prec
        sed -e "s/@@DOMAINE@@/$domain/g" \
                < "$NAMED_TEMPLATE" >> "$NAMED_CONF_FILE"
        add_to_named_reload "all"
    fi
}

remove_zone() {
    local domain="$1"
    local escaped_domain=`echo "$domain" | sed -e 's/\./\\\./g'`
    local zone_file="$ZONES_DIR/$domain"

    if [ -f "$zone_file" ]; then
        rm -f "$zone_file"
    fi

    if grep -q "\"$escaped_domain\"" "$NAMED_CONF_FILE"; then
        cp -a -f "$NAMED_CONF_FILE" "$NAMED_CONF_FILE.prec"
        cp -a -f "$NAMED_CONF_FILE" "$NAMED_CONF_FILE.$$"
        # That's for multi-line template
        #sed -e "/^zone \"$escaped_domain\"/,/^};/d" \
        # That's for one-line template
        grep -v "^zone \"$escaped_domain\"" \
            < "$NAMED_CONF_FILE" > "$NAMED_CONF_FILE.$$"
        mv -f "$NAMED_CONF_FILE.$$" "$NAMED_CONF_FILE"
        add_to_named_reload "all"
    fi
}

change_mx() {
    local domain="$1"
    local mx="$2"
    local zone_file="$ZONES_DIR/$domain"
    local pattern="^@*[[:space:]]*IN[[:space:]]*MX[[:space:]]*[[:digit:]]*[[:space:]].*\$"
    local mx_line="@ 	IN 	MX 	5 	$mx."

    # aller chercher le numéro de la ligne MX
    # XXX: comportement inconnu si plusieurs matchs ou MX commenté
    if grep -q "$pattern" "$zone_file"; then
        cp -a -f "$zone_file" "$zone_file.$$"
        sed -e "s/$pattern/$mx_line/" < "$zone_file" > "$zone_file.$$"
        mv "$zone_file.$$" "$zone_file"
    else
        echo "$mx_line" >> "$zone_file"
    fi

    increment_serial "$domain"
    add_to_named_reload "$domain"
}


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
       domaines_standby.mx,
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
