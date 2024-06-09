#!/bin/bash -e
#
# ----------------------------------------------------------------------
# AlternC - Web Hosting System
# Copyright (C) 2000-2016 by the AlternC Development Team.
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
# Original Author of file: Remi - 2016-04-27
# Purpose of file: Fixes permissions and ownerships of AlternC mailboxes
# ----------------------------------------------------------------------
#


show_help() {
cat << EOT
Usage: `basename $0` [-c] [-n] [-l <login>] [-u <uid>] [-p <directory>] [-d <domain>]

Fixes rights of AlternC mailboxes

	-c
		Compatibility mode: adapts rights for both pre 1.X  and newer versions (using acl)

	-l
		login of an AlternC account

	-u
		uid of an AlternC account

	-p
		path to a directory, if the path does not contain an underscore (_),
		this is considered as a prefix.

	-d
		fix mails belonging to a FQDN

	-n
		dry run. Causes the program to show the modifications, without actually executing them.

	-h 
		shows this help message

EOT
}

DRY_RUN=0
ACL=0

execute_cmd() {
	if [ $DRY_RUN -eq 1 ]; then
		echo $@
	else
		eval $@
	fi
}

query="select m.path, mem.uid from mailbox m join address a on m.address_id=a.id join domaines d on a.domain_id=d.id join membres mem on d.compte=mem.uid where delivery='dovecot'"

while getopts "hl:u:p:d:cn" optname
do
  case "$optname" in
  "c") 
     ACL=1
  ;;
  "n")
     DRY_RUN=1
  ;;
  ## login
  "l")
    if [[ "$OPTARG" =~ ^[a-zA-Z0-9_]+$ ]]; then
	query="$query and mem.login='$OPTARG'"
    else
	echo "error: \"$OPTARG\" is not a valid login" 1>&2
	show_help
	exit 1
    fi
  ;;
  ## uid
  "u")
    if [[ "$OPTARG" =~ ^[0-9]+$ ]]; then
	query="$query and mem.uid='$OPTARG'"
    else
	echo "error: \"$OPTARG\" is not a valid uid" 1>&2
	show_help
	exit 1
    fi
  ;;
  ## domain
  "d")
    if [[ "$OPTARG" != *"'"* ]]; then
	query="$query and d.domaine='$OPTARG'"
    fi
  ;;
  ## path
  "p")
    ## if path contains an underscore it's a full path, otherwise it's a prefix
    if [ -d "$OPTARG" ]; then
	if [[ $OPTARG == *"_"* ]]; then
		query="$query and m.path='${OPTARG%/}'"
	else
		query="$query and m.path LIKE '$OPTARG%'"
	fi
    else
	echo "error: \"$OPTARG\" is not a valid directory" 1>&2
	show_help
	exit 1
    fi
  ;;
  ## show help
  "h")
     show_help
     exit 0
  ;;
  "?")
     echo "Unkown option: $OPTARG" 1>&2
     show_help
     exit 1
  ;;
  *)
     show_help
     exit 1
  ;;
  esac
done


echo $query | mysql --defaults-file=/etc/alternc/my.cnf -N -B | while read path uid; do
    if [ -d "$path" ] 
    then
	echo "** Fixing $path ($uid)"

	if [ $ACL -eq 1 ]; then
		execute_cmd chown -R www-data.$uid $path
		execute_cmd find $path -type d -exec chmod 2755 {} \\\;
		execute_cmd setfacl -bknR -m d:u:$uid:rwx -m u:$uid:rwx -m d:o::--- -m o::---\
                    -m d:u:www-data:rwx -m u:www-data:rwx -m d:g:$uid:rwx -m g:$uid:rwx\
		    -m d:mask:rwx -m mask:rwx "$path"
        else 
		execute_cmd chown -R $uid.vmail $path
		execute_cmd find $path -type d -exec chmod 0700 {} \\\;
        fi
    else
	echo "** Skipping $path (does not exist)"
    fi
done
