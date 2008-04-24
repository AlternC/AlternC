#!/bin/sh

# $Id: sqlbackup.sh,v 1.9 2005/05/04 16:20:14 anarcat Exp $
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
# Original Author of file: Benjamin Sonntag - 2003-03-23
# Purpose of file: MySQL Database backup shell script for AlternC
# ----------------------------------------------------------------------

set -e

dobck() {
    local ext
    local i
    local old_ifs

    # mysql -B uses tab as a separator between fields, so we have to mess
    # with IFS in order to get the correct behaviour
    old_ifs="$IFS"
    IFS="	"
    while read login pass db count compressed target_dir; do
        IFS="$old_ifs"

        if [ "$compressed" -eq 1 ]; then
            ext=".gz"
        else
            ext=""
        fi
        i="$count"
       if [ ! -d "$target_dir" ] ; then
               echo "$target_dir is not a directory, skipping" >&2
               continue
       fi
        while [ "$i" -gt 1 ]; do
          next_i=$(($i - 1))
          mv -f "${target_dir}/${db}.sql.${next_i}${ext}" \
                "${target_dir}/${db}.sql.${i}${ext}" 2>/dev/null || true 
          i=$next_i # loop should end here
        done
        mv -f "${target_dir}/${db}.sql${ext}" \
              "${target_dir}/${db}.sql.${i}${ext}" 2>/dev/null || true 
        if [ "$compressed" -eq 1 ]; then
            mysqldump --defaults-file=/etc/alternc/my.cnf ${db} --add-drop-table --allow-keywords -Q -f -q -a -e |
                gzip -c > "${target_dir}/${db}.sql${ext}"
        else
            mysqldump --defaults-file=/etc/alternc/my.cnf ${db} --add-drop-table --allow-keywords -Q -f -q -a -e \
                > "${target_dir}/${db}.sql"
        fi

        IFS="	"
    done
    IFS="$old_ifs"
}

if [ "$1" = "daily" ]; then
    # Daily : 
    mode=2
else
    # weekly:
    mode=1
fi

/usr/bin/mysql --defaults-file=/etc/alternc/my.cnf -B << EOF | tail -n '+2' | dobck
SELECT login, pass, db, bck_history, bck_gzip, bck_dir
  FROM db
 WHERE bck_mode=$mode;
EOF

# vim: et sw=4
