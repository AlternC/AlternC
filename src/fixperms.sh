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

# four optionals argument to chose from
# -l string : a specific login to fix
# -u integer : a specific uid to fix
# -f string : a specific file to fix according to a given uid 
# -d string : a specific subdirectory to fix according to a given uid 

# The u and l switch are used to fix a given user whole directory including his base directory ($ALTERNC_HTML/<letter>/<login>/
# The f and d switch are used to fix a given file or directory under the user's base directory. They use the base directory to get the permissions they should use.
# Be sure to have correct base directory permissions before attemplting to fix use those two switch 

query="SELECT uid,login FROM membres"
sub_dir=""
file=""

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
    #Is this kinf of escaping enough ?
    file=$(printf %q $OPTARG)
    echo $file
  ;;
  "d")
    #Is this kinf of escaping enough ?
    sub_dir=$(printf %q $OPTARG)
    echo $sub_dir
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


PATH=/sbin:/bin:/usr/sbin:/usr/bin
umask 022

CONFIG_FILE="/usr/lib/alternc/functions.sh"
if [ ! -r "$CONFIG_FILE" ]; then
    echo "Can't access $CONFIG_FILE."
    exit 1
fi
source "$CONFIG_FILE"

if [ `id -u` -ne 0 ]; then
    echo "$0 must be launched as root"
    exit 1
fi


doone() {
    read GID LOGIN || true
    while [ "$LOGIN" ] ; do
      if [ "$DEBUG" ]; then
        echo "Setting rights and ownership for user $LOGIN having gid $GID"
      fi
      REP="$(get_html_path_by_name "$LOGIN")"
  
      # Clean the line, then add a ligne indicating current working directory
      printf '\r%*s' "${COLUMNS:-$(tput cols)}" ''
      printf "\r%${COLUMNS}s" "AlternC fixperms.sh -> working on $REP"

      # Set the file readable only for the AlternC User
      mkdir -p "$REP"
      chown -R $GID:$GID "$REP"
      chmod 2770 -R "$REP"

#      # Delete existings ACL
#      # Set the defaults acl on all the files
#      setfacl -b -k -n -R -m d:g:alterncpanel:rwx -m d:u::rwx -m d:g::rwx -m d:u:$GID:rwx -m d:g:$GID:rwx -m d:o::--- -m d:mask:rwx\
#                    -Rm   g:alterncpanel:rwx -m u:$GID:rwx -m g:$GID:rwx -m mask:rwx\
#               "$REP"
      setfacl -bknR -m d:u:alterncpanel:rwx -m d:g:alterncpanel:rwx -m u:alterncpanel:rwx -m g:alterncpanel:rwx -m d:o::--- -m o::---\
                    -m d:u:$GID:rwx -m d:g:$GID:rwx -m u:$GID:rwx -m g:$GID:rwx -m d:mask:rwx -m mask:rwx "$REP"

      fixtmp $GID
      read GID LOGIN || true
    done
    echo -e "\nDone" 
}

fixdir() {
      if [ "$DEBUG" ]; then
        echo "Setting rights with fixdir"
      fi

      # sub_dir is global
      REP="$sub_dir"
      # We assume that the owner of the directory should be the one from the html user base directory ( $ALTERNC_HTML/<letter>/<login>) 
      REP_ID="$(get_uid_by_path "$REP")"
      # Clean the line, then add a ligne indicating current working directory
      printf '\r%*s' "${COLUMNS:-$(tput cols)}" ''
      printf "\r%${COLUMNS}s" "AlternC fixperms.sh -> working on $REP"

      # Set the file readable only for the AlternC User
      mkdir -p "$REP"
      chown -R $REP_ID:$REP_ID "$REP"

      # Delete existings ACL
      # Set the defaults acl on all the files
#      setfacl -b -k -n -R -m d:g:alterncpanel:rwx -m d:u::rwx -m d:g::rwx -m d:u:$REP_ID:rwx -m d:g:$REP_ID:rwx -m d:o::--- -m d:mask:rwx\
#                    -Rm   g:alterncpanel:rwx -m u:$REP_ID:rwx -m g:$REP_ID:rwx -m mask:rwx\
#               "$REP"
      setfacl -bknR -m d:u:alterncpanel:rwx -m d:g:alterncpanel:rwx -m u:alterncpanel:rwx -m g:alterncpanel:rwx -m d:o::--- -m o::---\
                    -m d:u:$REP_ID:rwx -m d:g:$REP_ID:rwx -m u:$REP_ID:rwx -m g:$REP_ID:rwx -m d:mask:rwx -m mask:rwx "$REP"

      fixtmp $REP_ID
      echo -e "\nDone" 
}

fixtmp() {
  REP_ID=$1
  local REP=$(get_html_path_by_name $(get_name_by_uid $REP_ID))

  if [ "$REP/tmp" == "/tmp" ] ; then 
    echo ERROR 
    exit 0
  fi
  
  test -d "$REP/tmp" || ( mkdir "$REP/tmp" && setfacl -bkn -m d:u:alterncpanel:rwx -m d:g:alterncpanel:rwx -m u:alterncpanel:rwx -m g:alterncpanel:rwx -m d:o::--- -m o::--- -m d:u:$REP_ID:rwx -m d:g:$REP_ID:rwx -m u:$REP_ID:rwx -m g:$REP_ID:rwx -m d:mask:rwx -m mask:rwx "$REP" )  

  chmod 777 "$REP/tmp"
}

fixfile() {
      /usr/bin/setfacl -bk "$file"
      # We assume that the owner of the file should be the one from the html user base directory ( $ALTERNC_HTML/<letter>/<login>) 
      REP_ID="$(get_uid_by_path "$file")"
      chown $REP_ID:$REP_ID "$file"
      chmod 0770 "$file"
      /usr/bin/setfacl  -m u:$REP_ID:rw- -m g:$REP_ID:rw- -m u:alterncpanel:rw- -m g:alterncpanel:rw- "$file"
      echo file ownership and ACLs changed
}

ctrl_c() {
  echo -e "\n***** INTERRUPT *****"
  echo "$0 was interrupted. Default is to return an error code."
  echo "Do you want to *ignore* the error code (y/n)?"
  echo "(default is n)"
  read -N 1 ans
  case "$ans" in 
    y|Y )
      exit 0
      ;;
    * )
      exit -5
      ;;
  esac
}

trap ctrl_c SIGINT

#Start of the script actions
if [[ "$file" != "" ]]; then # if we are dealing with a file
  if [ -e "$file" ]; then
    fixfile
  else
		echo "file not found"
	fi
elif [[ "$sub_dir" != "" ]]; then #if we are dealing with a directory
	if [ -d "$sub_dir" ]; then
	  fixdir
	else
echo "dir not found"
	fi
else
  #we are fixing the whole html directory
  #either for all user (default) or a specific one ( -u or -l switch )
	mysql --defaults-file=/etc/alternc/my.cnf --skip-column-names -B -e "$query" |doone
fi
