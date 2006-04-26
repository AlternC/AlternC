#!/bin/sh -e
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
rootlogin=$1
rootpass=$2
systemdb=$3

mysql="mysql --defaults-file=/etc/mysql/debian.cnf"

if ! $mysql mysql -e "SHOW TABLES" >/dev/null
then
    # is this an upgrade then?
    mysql="mysql -u $rootlogin -p$rootpass" 
    if ! $mysql mysql -e "SHOW TABLES" >/dev/null
    then
        echo "Can't get proper credentials, aborting"
        exit 1
    fi
fi

echo "Setting AlternC $systemdb system table and privileges "
$mysql -e "CREATE DATABASE IF NOT EXISTS $systemdb;" 
echo "Installing AlternC schema "
$mysql $systemdb < /usr/share/alternc/install/mysql.sql

echo "Granting users "
$mysql -e "GRANT ALL ON *.* TO '$rootlogin'@'${MYSQL_CLIENT}' IDENTIFIED BY '$rootpass' WITH GRANT OPTION" 

mysql -u $rootlogin -p$rootpass $systemdb -e "SHOW TABLES" >/dev/null && echo "MYSQL.SH OK!" || echo "MYSQL.SH FAILED!"