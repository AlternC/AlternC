#!/bin/sh -e

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
    while [ "$LOGIN" ] 
      do
      if [ "$DEBUG" ]; then
	  echo "Setting rights and ownership for user $LOGIN having gid $GID"
      fi
      INITIALE=`echo $LOGIN |cut -c1`
      REP="$ALTERNC_LOC/html/$INITIALE/$LOGIN"
            
      find $REP -type d -exec chmod g+s \{\} \;
	  chown -R 33.$GID $REP
	  read GID LOGIN
    done
}

mysql --defaults-file=/etc/alternc/my.cnf -B -e "select uid,login from membres" |grep -v ^uid|doone

