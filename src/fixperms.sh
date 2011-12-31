#!/bin/bash -e

#
# $Id: fixperms.sh,v 1.1 2005/08/29 19:21:31 benjamin Exp $
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
# Original Author of file: Benjamin Sonntag for Metaconsult
# Purpose of file: Fix permission and ownership of html files
# ----------------------------------------------------------------------
#

#Default Query : fixperms for all account
query="SELECT uid,login FROM membres"

#Two optionals argument
# -l string : a specific login to fix
# -u interger : a specifi uid to fix
while getopts "l:u:" optname
  do
    case "$optname" in
      "l")
        query="SELECT uid,login FROM membres WHERE login LIKE '$OPTARG'"
        ;;
      "u")
        query="SELECT uid,login FROM membres WHERE uid LIKE '$OPTARG'"
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
      REP="$ALTERNC_LOC/html/$INITIALE/$LOGIN"

      # Set the file readable only for the AlternC User
      chown -R $GID:$GID "$REP"
      chmod 2770 -R "$REP"

      # Delete existings ACL
      # Set the defaults acl on all the files
      setfacl -b -k -m d:g:alterncpanel:rw- -m d:u:$GID:rw- -m d:g:$GID:rw- \
                    -m   g:alterncpanel:rw- -m   u:$GID:rw- -m   g:$GID:rw- \
              -R "$REP"

      read GID LOGIN
    done
}

mysql --defaults-file=/etc/alternc/my.cnf -B -e "$query" |grep -v ^uid|doone
