#!/bin/sh
#
# $Id: initrep.sh,v 1.13 2004/11/10 22:28:34 anonymous Exp $
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
# Original Author of file: Jerome Moinet, Benjamin Sonntag
# Purpose of file: Create all the directories for AlternC in /$DATA/
# ----------------------------------------------------------------------
#
DATA="/var/alternc"

for sub in bureau cgi-bin db dns/redir exec.usr html mail mla tmp apacheconf
do
  mkdir -p $DATA/$sub
done

chmod 1777 $DATA/tmp

for i in a b c d e f g h i j k l m n o p q r s t u v w x y z _ 0 1 2 3 4 5 6 7 8 9
do
    for sub in dns dns/redir mail html apacheconf
    do
      mkdir -p $DATA/$sub/$i
      chown www-data $DATA/$sub/$i
    done
done

touch $DATA/apacheconf/override_php.conf
mkdir -p /var/log/alternc
chown www-data:www-data /var/log/alternc
chgrp -R www-data /etc/alternc
