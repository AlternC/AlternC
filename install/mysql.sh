#!/bin/sh 
#
# $Id: mysql.sh,v 1.11 2006/01/11 22:51:28 anarcat Exp $
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
# Original Author of file: Benjamin Sonntag
# Purpose of file: Install a fresh new mysql database system
# USAGE : "mysql.sh loginroot passroot systemdb"
# ----------------------------------------------------------------------
#

sqlserver="$1"
rootlogin="$2"
rootpass="$3"
systemdb="$4"

if [ -z "$rootlogin" -o -z "$rootpass" -o -z "$systemdb" ]
then
    echo "Usage: mysql.sh <mysqlserver> <rootlogin> <rootpass> <systemdb>"
    exit 1
fi

mysql="/usr/bin/mysql --defaults-file=/etc/mysql/debian.cnf -h$sqlserver "

# The grant all is the most important right needed in this script.
# If this call fail, we may be connected to a mysql-server version 5.0.
echo "Granting users "
# In that case, change mysql parameters and retry. Use root / nopassword.
$mysql -e "GRANT ALL ON *.* TO '$rootlogin'@'${MYSQL_CLIENT}' IDENTIFIED BY '$rootpass' WITH GRANT OPTION"
if [ "$?" -ne "0" ]
then
    echo "debian-sys-maintainer doesn't have the right credentials, assuming we're doing an upgrade"
    mysql="/usr/bin/mysql -h$sqlserver -u$rootlogin -p$rootpass" 
    $mysql -e "GRANT ALL ON *.* TO '$rootlogin'@'${MYSQL_CLIENT}' IDENTIFIED BY '$rootpass' WITH GRANT OPTION"
    if [ "$?" -ne "0" ] 
	then 
        echo "Still not working, assuming clean install and empty root password"
        mysql="/usr/bin/mysql -h$sqlserver -uroot "
        $mysql -e "GRANT ALL ON *.* TO '$rootlogin'@'${MYSQL_CLIENT}' IDENTIFIED BY '$rootpass' WITH GRANT OPTION"
        if [ "$?" -ne "0" ] 
        then 
	    echo "Can't grant system user $rootlogin, abording"; 
	    exit 1 
	fi
    fi
fi

# Now we can use rootlogin and rootpass. 
mysql="/usr/bin/mysql -h$sqlserver -u$rootlogin -p$rootpass" 

echo "Setting AlternC '$systemdb' system table and privileges "
$mysql -e "CREATE DATABASE IF NOT EXISTS $systemdb;" 

echo "Installing AlternC schema "
$mysql $systemdb < /usr/share/alternc/install/mysql.sql

/usr/bin/mysql -h$sqlserver -u$rootlogin -p$rootpass $systemdb -e "SHOW TABLES" >/dev/null && echo "MYSQL.SH OK!" || echo "MYSQL.SH FAILED!"
