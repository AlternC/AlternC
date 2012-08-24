#!/bin/bash -e
#
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
# Purpose of file: Fix permission, ACL and ownership of AlternC's files
# ----------------------------------------------------------------------
#

# Default Query : fixperms for all account
query="SELECT uid,login FROM membres"
sub_dir=""
file=""
# Two optionals argument
# -l string : a specific login to fix
# -u integer : a specific uid to fix
# -f integer : a specific file to fix according to a given uid 

while getopts "l:u:f:d:" optname
  do
    case "$optname" in
      "l")
		if [[ "$OPTARG" =~ ^[a-zA-Z0-9_]+$ ]] ; then
        	query="SELECT uid,login FROM membres WHERE login LIKE '$OPTARG'"
		else	
			echo "Bad login provided"
			exit
		fi
        ;;
      "u")
		if [[ "$OPTARG" =~ ^[0-9]+$ ]] ; then
			query="SELECT uid,login FROM membres WHERE uid LIKE '$OPTARG'"
		else
			echo "Bad uid provided"
			exit
		fi
        ;;
      "f")
		  file="$OPTARG"
        ;;
      "d")
        sub_dir="$OPTARG"
        ;;
      "?")
        echo "Unknown option $OPTARG - stop processing"
        exit
        ;;
      ":")
        echo "No argument value for option $OPTARG - stop processing"
        exit
        ;;
      *)
      # Should not occur
        echo "Unknown error while processing options"
        exit
        ;;
    esac
  done

CONFIG_FILE="/etc/alternc/local.sh"

PATH=/sbin:/bin:/usr/sbin:/usr/bin

umask 022

if [ ! -r "$CONFIG_FILE" ]; then
    echo "Can't access $CONFIG_FILE."
    exit 1
fi

if [ `id -u` -ne 0 ]; then
    echo "fixperms.sh must be launched as root"
    exit 1
fi

. "$CONFIG_FILE"

doone() {
    read GID LOGIN
    while [ "$LOGIN" ] ; do
      if [ "$DEBUG" ]; then
        echo "Setting rights and ownership for user $LOGIN having gid $GID"
      fi
      INITIALE=`echo $LOGIN |cut -c1`
      REP="$ALTERNC_LOC/html/$INITIALE/$LOGIN/$sub_dir"

      # Set the file readable only for the AlternC User
      chown -R alterncpanel:$GID "$REP"
      chmod 2770 -R "$REP"

      # Delete existings ACL
      # Set the defaults acl on all the files
      setfacl -b -k -n -R -m d:g:alterncpanel:rwx -m d:u::rwx -m d:g::rwx -m d:u:$GID:rwx -m d:g:$GID:rwx -m d:o::--- -m d:mask:rwx\
                    -Rm   g:alterncpanel:rwx -m u:$GID:rwx -m g:$GID:rwx -m mask:rwx\
               "$REP"

      read GID LOGIN || true
    done
}

fixfile() {
	read GID LOGIN
	/usr/bin/setfacl -bk $file
	echo "gid: $GID"
	echo "file: $file"
	chown alterncpanel:$GID $file
	chmod 0770 $file
	/usr/bin/setfacl  -m u:$GID:rw- -m g:$GID:rw- -m g:alterncpanel:rw- -m u:$GID:rw- -m g:$GID:rw- $file
	echo file ownership and ACLs changed

}

if [[ "$file" != "" ]]; then
	if [ -e "$file" ]; then
		mysql --defaults-file=/etc/alternc/my.cnf --skip-column-names -B -e "$query" |fixfile
	else
		echo "file not found"
	fi
else
	mysql --defaults-file=/etc/alternc/my.cnf --skip-column-names -B -e "$query" |doone
fi
