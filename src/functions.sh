#!/bin/bash

# ----------------------------------------------------------------------
# AlternC - Web Hosting System
# Copyright (C) 2000-2012 by the AlternC Development Team.
# https://alternc.org/
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
# Purpose of file: Main functions used for any bash script for AlternC.
# ----------------------------------------------------------------------

# Init some vars
. /etc/alternc/local.sh


# Init some other vars
MYSQL_DO="/usr/bin/mysql --defaults-file=/etc/alternc/my.cnf -Bs -e "
mysql_query() { /usr/bin/mysql --defaults-file=/etc/alternc/my.cnf -Bs -e "$@" ; }
DOMAIN_LOG_FILE="/var/log/alternc/update_domains.log"
VHOST_FILE="$VHOST_DIR/vhosts_all.conf" 
VHOST_MANUALCONF="$VHOST_DIR/manual/"
LOCK_JOBS="/var/run/alternc/jobs-lock"


# Some useful miscellaneous shell functions
print_domain_letter() {
  local domain=$1
  domain=${domain/.${domain/*./}/}
  domain=${domain/*./}
  domain=${domain:0:1}
  # Bash match a 'é' when we give him [a-z]. Strange
  if [[ "$domain" =~ [ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0-9]{1} ]]; then
    echo $domain
  else
    echo '_'
  fi 
}

get_uid_by_path() {
  local path="$1"
  local sizepath=${#path}
  local lastcar=${ALTERNC_HTML: -1}

  local sizebasepath=${#ALTERNC_HTML}
  if [ "${ALTERNC_HTML:-1}" != "/" ] ; then
    sizebasepath=$(( $sizebasepath + 1 ))
  fi

  local login=$(echo ${path:$sizebasepath} | cut -d '/' -f 2)

  get_uid_by_name $login
}

# Return the html path for a account name
get_html_path_by_name() {
  local name="$1"
  if [[ ! "$name" =~ ^([a-z0-9]+)$ ]] ; then
    # Error on error flux
    echo "Account name is incorrect." >&2
    exit 2
  fi
  echo "$ALTERNC_HTML/${name:0:1}/$name"
}

# echoes the first letter of an alternc account name.
print_user_letter() {
    local user="$1"
    echo ${user:0:1}
}


# return the uid of an alternc account
get_uid_by_name() {
  local name=$1
  if [[ ! "$name" =~ ^([a-z0-9]+)$ ]] ; then
    # Error on error flux
    echo "Account name is incorrect." >&2
    exit 2
  fi
   mysql_query 'SELECT uid FROM membres WHERE login="'"$name"'" LIMIT 1;'
}

get_name_by_uid() {
  local id=$1
  if [[ ! "$id" =~ ^([0-9]+)$ ]] ; then
    # Error on error flux
    echo "Account name is incorrect." >&2
    exit 2
  fi
  mysql_query 'SELECT login FROM membres WHERE uid="'"$id"'" LIMIT 1;'
}

get_variable_from_db() {
  mysql_query 'SELECT value FROM membres WHERE name="'"$1"'" LIMIT 1;'
}


# imprime le nom d'usager associé au domaine
get_account_by_domain() {
        mysql_query 'SELECT a.login FROM membres a, sub_domaines b WHERE a.uid = b.compte AND \
        CONCAT(IF(sub="", "", CONCAT(sub, ".")), domaine) = "'"$1"'" LIMIT 1;'
}

get_uid_by_domain() {
        mysql_query 'SELECT b.compte as uid FROM sub_domaines b WHERE \
        CONCAT(IF(sub="", "", CONCAT(sub, ".")), domaine) = "'"$1"'" LIMIT 1;'
}

# Log (echoes+log) an error and exit the current script with an error.
log_error() {
  local error=$1
  echo "`date` $0 : $1" | tee -a "$DOMAIN_LOG_FILE" >&2
  exit 1
}

generate_string() {
  local size=$1
  if [ -z "$size" ] ; then
    size=20
  fi
  < /dev/urandom tr -dc _A-Z-a-z-0-9 | head -c${1:-$size}
  echo
}

lock_jobs() {
  test -d "$(dirname "$LOCK_JOBS")" || mkdir -p "$(dirname "$LOCK_JOBS")"
  touch "$LOCK_JOBS"
}

unlock_jobs() {
  test -e "$LOCK_JOBS" && rm -f "$LOCK_JOBS"
}

are_jobs_locked() {
  return $(test -e "$LOCK_JOBS")
}

stop_if_jobs_locked() {
  are_jobs_locked || return
  echo "There is a file $LOCK_JOBS"
  echo "So no jobs are allowed, not even for $0"
  echo "Did you launch alternc.install ?"
  exit 42
}
