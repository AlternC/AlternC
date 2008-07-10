#!/bin/bash

# $Id: sqlbackup.sh,v 2.0 2006/10/17 17:32:05 mistur Exp $
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
# Changed by Yoann Moulin : 2006-10-16
# * Adding an other possibilty for name of the backup files which
#   avoid renaming old backup files (name rotation methode)
#   this methode include the date of the backup day in the name of the
#   file
#   Usefull for person who use rsync, rsnapshot, etc... this methode 
#   avoid to sync old files which just has been rename but seem diff
#   for sync script
set -e

# Get mysql user and password : 
. /etc/alternc/local.sh

# get the date of the day
DATE=`date +"%Y%m%d"`

# echo function, used for output wrapping when run in daemon 
# mode.
# usage: print [option] <message>
#   without option, print <message> in any case on the stdout  
#
# options :
#   error : print <message> in any case and indicate that an error message
#   debug : print <message> if debug mode is active
#   info  : print <message> if verbose mode is active
#
# notes :
#   if backup running in daemon mode, printing in log file if an otpion
#   is gived to the function
print() {
    
    # if a log level is given to the print function
    # 'error', 'info' or 'debug'
    log_level=""
    if [ "$1" == "error" ] || [ "$1" == "info" ] || [ "$1" == "debug" ]; 
    then
        # read it and remove it for arg list
        log_level="$1"
        shift
    fi

    # if
    #  - No log level is specified 
    #  - Log level equal to 'error' 
    #    => print in any case on stdout
    #    => add to log file as well if $DAEMON set to 'ON'
    #  - Log level equal to 'debug' and $DEBUG is set to on
    #  - Log level equal to 'info' and $VERBOSE set to 'ON'
    #     => print on log file if $DAEMON set to 'ON', on stdout if not
    if [ -z "$log_level" ] || 
    [ "$log_level" == "error" ] ||
    [ "$DEBUG" == "ON"  -a  "$log_level" == "debug" ]  ||
    [ "$log_level" == "info" -a  "$VERBOSE" == "ON" ] ;
    then
        if [ "$DAEMON" == "ON" ] ; then
            # function without option must be print on stdout in anycase 
            # even if print in the log file
            if [ -z "$log_level" ] || [ "$log_level" == "error" ];
            then
                echo "$EXEC_CMD $log_level: $*"
            fi
            logger -p local0.$log_level -t sqlbackup "$*"
        else
            if [ -z "$log_level" ];
            then
                echo "$*"
            else
                echo "$log_level: $*"
            fi
        fi
    fi
    
}

error() {
    print "error" $*
}

info() {
    print "info" $*
}
debug() {
    print "debug" $*
}

function dobck() {
    local ext
    local i
    local old_ifs
    
    # mysql -B uses tab as a separator between fields, so we have to mess
    # with IFS in order to get the correct behaviour
    old_ifs="$IFS"
    IFS="	"
    # read parameter given by mysql
    while read login pass db count compressed target_dir; do
        
        debug "read $login \$pass $db $count $compressed $target_dir"
        # restore $IFS after read parameter
        IFS="$old_ifs"

        # by default : DOBAKCUP set to yes
        DO_BACKUP="YES"
        
        if [ "$compressed" -eq 1 ]; then
            ext=".gz"
        else
            ext=""
        fi

        # if $SQLBACKUP_TYPE is set to "rotate" classical rotation files methode will be used
        # use incrementale number in the name of files where the highest number indicate
        # the oldest files
        # if the rotate type is not set or set to date, the name of the export file will contain the date
        # of the backup on won't be rotate by the classic rotate number
        # usefull if you're using rsync or rsnapshop or everything base on rsync to avoir to copy
        # rotate files which just change name
        #
        # ------------------------------------------------------------------ #
        # the variable SQLBACKUP_TYPE must be set in /etc/alternc/local.sh #
        # ------------------------------------------------------------------ #
        if [ $SQLBACKUP_TYPE == "rotate" ]; then 
            
            i="$count"
            
            # rotate all backup
            while [ $i -gt 1 ] ; do
              
              next_i=$(($i - 1))
            
              if [ -e "${target_dir}/${db}.sql.${next_i}${ext}" ]; then
                mv -f "${target_dir}/${db}.sql.${next_i}${ext}" \
                      "${target_dir}/${db}.sql.${i}${ext}" 2>/dev/null
              fi
              i=$next_i # loop should end here
            done
            
            # move most recently backup with a rotate file name
            if [ -e "${target_dir}/${db}.sql${ext}" ]; then
              mv -f "${target_dir}/${db}.sql${ext}" \
                    "${target_dir}/${db}.sql.${i}${ext}" 2>/dev/null
            fi

            name_backup_file="${db}" 
         else   
            # ---------------
            # default methode    
            # ---------------
            # calcul the mtime parameter for find
            # $count is the number of backup to keep
            # daily  : if we are keeping X backup, deleting the file which has the mtime at X + 1 days
            # weekly : if we are keeping X backup, deleting the file which has the mtime at (X + 1) * 7 day
            # echo "last2del=( $count + 1 ) * $coef "
            #
            last2del=$(( ( $count + 1 ) * $coef ))
           
            # find the oldest backup file need to be delete
            # find ${target_dir}     : in the target_dir
            # -name \"${db}.*sql.*\" : All files like <db_name>.*sql.*
            # -maxdepth 0            : only in the target dir (on not in the subdirectory) 
            # -mtime $last2del       : files with the exact mtime set to $last2del 
            #                          daily  : ( number of backup to keep + 1 ) days 
            #                          weekly : ( number of backup to keep + 1 ) * 7 days
            # -exec rm -f {} \;      : remove all files found
            # 
            debug "find ${target_dir} -name \"${db}.*sql${ext}\" -maxdepth 1 -mtime +$last2del -exec rm -f {} \; -ls"
            find ${target_dir} -name "${db}.*sql${ext}" -maxdepth 1 -mtime +${last2del} -exec rm -f {} \; -ls
            
            # set the name of the backup file with the date of the day
            name_backup_file="${db}.${DATE}"
            
       fi
      
       # if the backup exite and SQLBACKUP_OVERWRITE is set to NO, cancel backup
       if [ -f "${target_dir}/${name_backup_file}.sql${ext}" ] && [ "$SQLBACKUP_OVERWRITE"  == "no" ] ; then
           
           info "sqlbackup.sh: ${target_dir}/${name_backup_file}.sql${ext}: already exist"
           info "              => no backup done as specify in allow-overwrite = $SQLBACKUP_OVERWRITE"
           DO_BACKUP="NO"

        # if the backup exite and SQLBACKUP_OVERWRITE is set to RENAME, add  
        elif [ -f "${target_dir}/${name_backup_file}.sql${ext}" ] && [ "$SQLBACKUP_OVERWRITE"  == "rename" ] ; then

           info "sqlbackup.sh: ${target_dir}/${name_backup_file}.sql${ext}: already exist"
           info "              => renaming the new file as specify in allow-overwrite = $SQLBACKUP_OVERWRITE"
           hours=`date +"%H%M"` 
           name_backup_file="${name_backup_file}.${hours}"

        # if the backup exite and SQLBACKUP_OVERWRITE is set OVERWRITE, add  
        elif [ -f "${target_dir}/${name_backup_file}.sql${ext}" ] && [ "$SQLBACKUP_OVERWRITE"  == "overwrite" ] ; then

           info "sqlbackup.sh: ${target_dir}/${name_backup_file}.sql${ext}: already exist"
           info "              => overwrite file as specify in allow-overwrite = $SQLBACKUP_OVERWRITE"
           
       fi

       ###
       # mysqldump Option :
       # --add-drop-table  : Add a 'drop table' before each create.
       #                     usefull if you want to override the database without delete table before
       #                     this is need to used restore from the alternc interface
       # --allow-keywords  : Allow creation of column names that are keywords.
       #                     
       # --quote-names     : Quote table and column names with `
       #                     Usefull if you have space in table or column names
       # --force           : Continue even if we get an sql-error. 
       #                     To avoid end of script during backup script execution
       #                     Allow script to backup other database if one of the have an error
       # --quick           : Don't buffer query, dump directly to stdout. 
       #                     optimisation option
       # --all             : Include all MySQL specific create options.
       #                     Permit keep information like type or comment
       # --extended-insert : Allows utilization of the new, much faster INSERT syntax.
       #                     optimization option
       # (--add-locks       : Add locks around insert statements.)
       # (--lock-tables     : Lock all tables for read.)
       #                      those 2 options avoid insert during dump which can create an unconsistent 
       #                      state of the database backup
       #                      remove because lock is allow for alternc user 
       if [ "$DO_BACKUP" == "YES" ]; then
           command="mysqldump --defaults-file=/etc/alternc/my.cnf --add-drop-table --allow-keywords --quote-names --force --quick --all --extended-insert $db"
           if [ "$compressed" -eq 1 ] ; then
               $command = "$command | gzip -c"
           fi
           debug "$command > ${target_dir}/${name_backup_file}.sql${ext}"
           $command > "${target_dir}/${name_backup_file}.sql${ext}"
        fi

        IFS="	"
    done
    IFS="$old_ifs"
}

# read_parameters gets all command-line arguments and analyzes them
# 
# return: 
read_parameters() {

    # for all parameter give to the script
    while [ "$1" != "" ] ; do
        case "$1" in
            -h|--help) usage; exit ;;
            -v|--verbose) VERBOSE="ON" ;;
            -d|--debug) DEBUG="ON" ;;
            -t|--type) shift; TYPE="$1";;
            -n|--name-methode) shift; SQLBACKUP_TYPE="$1";;
            -a|--allow-ovewrite) shift; SQLBACKUP_OVERWRITE="$1" ;;
            *)
                error "invalid option -- $1" 
                error "Try \`sqlbackup.sh --help' for more information."
                exit ;;
        esac
        # in case of no argument give to an option
        # shift execute an exit if already empty
        # add test to avoid this at least to print error message
        [ "$1" != "" ] && shift   
    done

    debug "TYPE = $TYPE"
    debug "SQLBACKUP_TYPE = $SQLBACKUP_TYPE"
    debug "SQLBACKUP_OVERWRITE = $SQLBACKUP_OVERWRITE"
   

    # check options 
    if [ "$TYPE" == "daily" ]; then
        # Daily : 
        mode=2
        coef=1
    elif [ "$TYPE" == "weekly" ] ; then
        # Weekly:
        mode=1
        coef=7
    elif [ -n "$TYPE" ] ; then
        error "missing argument: type"
        error "Try \`sqlbackup.sh --help' for more information."
        exit
    else 
        error "invalid argument: type -- $TYPE"
        error "Try \`sqlbackup.sh --help' for more information."
        exit
    fi

    if ! ( [ -z "$SQLBACKUP_TYPE" ] || 
           [ "$SQLBACKUP_TYPE" == "date" ] || 
           [ "$SQLBACKUP_TYPE" == "rotate" ] ) ; then
        error "invalid argument: name-methode -- $SQLBACKUP_TYPE"
        error "Try \`sqlbackup.sh --help' for more information."
        exit
     fi

    if ! ( [ -z  "$SQLBACKUP_OVERWRITE" ] || 
           [ "$SQLBACKUP_OVERWRITE" == "no" ] || 
           [ "$SQLBACKUP_OVERWRITE" == "rename" ] || 
           [ "$SQLBACKUP_OVERWRITE" == "overwrite" ] ); then
        error "invalid argument: allow-ovewrite -- $SQLBACKUP_OVERWRITE"
        error "Try \`sqlbackup.sh --help' for more information."
        exit
     fi

}

# a quick intro to the software, displayed when no params found
usage() {
    echo "Usage: sqlbackup.sh [OPTION] -t TYPE

sqlbackup.sh is a script used by alternc for sql backup

Mandatory arguments to long options are mandatory for short options too.
  -v, --verbose                 set verbose mode on
  -d, --debug                   set debug mode on
  -n, --name-method  METHOD     set the method type for files' name
  -a, --allow-override OVERRIDE specify the behaviour if backup files already exist
  -t, --type TYPE               set backup type
  -h, --help                    display this help and exit

the TYPE arguments specify type of backup.  Here are the values:

    daily           Execute a daily backup on all databases set to daily backup
    weekly          Execute a daily backup on all databases set to weekly backup

the METHOD argument the type for files' name.  Here are the values:

    date            insert in the backup file's name the date of the backup
                    (default value)
    rotate          rename file as file.<number><extension> where <number>
                    is incremented

the OVERRIDE argument the behaviour of the script if a backup file already exist.
Here are the values:

    no              if a backup file already exist, no backup done
    rename          if a backup file already exist, add an extension to the new 
                    backup file

    overwrite       if a backup file already exist, overwrite it with the new  
                    backup"

}
debug begin $@
# read all paramter before doing anything before
read_parameters $@
debug end

###
# select backup information from the alternc database in the db table 
# all backup for the specify mode (daily or weekly)
# option :
#   --batch : Print results with a tab as separator, each row on a new line. 
#             avoid seperator like "|" which are not usefull in a shell script
#             need to set the IFS environment variable to "\t" (tabbulation) for 
#             the `read' command (indicate field separator by default `read'
#             use space)
# tail -n '+2' permit to skip the first line (legende line)
# execut dobck on all database found by the sql request
#
# the "<< EOF" mean send data to the command until EOF (end of file)
#
debug /usr/bin/mysql --defaults-file=/etc/alternc/my.cnf --batch
/usr/bin/mysql --defaults-file=/etc/alternc/my.cnf --batch << EOF | tail -n '+2' | dobck
SELECT login, pass, db, bck_history, bck_gzip, bck_dir
  FROM db
 WHERE bck_mode=$mode;
EOF

# vim: et sw=4
