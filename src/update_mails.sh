#!/bin/bash
# This script look in the database wich mail should be DELETEd

# Source some configuration file
for CONFIG_FILE in \
      /etc/alternc/local.sh \
      /usr/lib/alternc/functions.sh
  do
    if [ ! -r "$CONFIG_FILE" ]; then
        echo "Can't access $CONFIG_FILE."
        exit 1
    fi
    . "$CONFIG_FILE"
done

LOCK_FILE="/var/run/alternc/update_mails"

# ALTERNC_MAIL is from local.sh

# Somes check before start operations
if [ `id -u` -ne 0 ]; then
    log_error "must be launched as root"
elif [ -f "$LOCK_FILE" ]; then
    process=$(ps f -p `cat "$LOCK_FILE"|tail -1`|tail -1|awk '{print $NF;}')
    if [ "$(basename $process)" = "$(basename "$0")" ] ; then
      log_error "last cron unfinished or stale lock file ($LOCK_FILE)."
    else
      rm "$LOCK_FILE"
    fi
fi

# If there is ionice, add it to the command line
ionice=""
ionice > /dev/null && ionice="ionice -c 3 "

# We lock the application
echo $$ > "$LOCK_FILE"

# List the local addresses to DELETE
# Foreach => Mark for deleting and start deleting the files
# If process is interrupted, the row isn't deleted. We have to force it by reseting mail_action to 'DELETE'
mysql_query "SELECT id, address_id, quote(replace(path,'!','\\!')) FROM mailbox WHERE mail_action='DELETE';"|while read id address_id path ; do
  mysql_query "UPDATE mailbox set mail_action='DELETING' WHERE id=$id;"
  /usr/lib/alternc/mail_dodelete.php "$address_id"
  # Check there is no instruction of changing directory, and check the first part of the string
  if [[ "$path" =~ '../' || "$path" =~ '/..' || ! "'$ALTERNC_MAIL'" == "${path:0:$((${#ALTERNC_MAIL}+1))}'" ]] ; then
    # The path will be empty for mailman addresses
    if [[ "$path" != "''" ]]; then
    	echo "Error : this directory will not be deleted, pattern incorrect"
    	continue
    fi
  fi

  # If no dir, DELETE
  # If dir and rm ok, DELETE
  # Other case, do nothing
  if [ -d "${path//\'/}" ] ; then 
    $ionice rm -rf "${path//\'/}" && mysql_query "DELETE FROM mailbox WHERE id=$id AND mail_action='DELETING';"
    # Do the rm again in case of newly added file during delete. Should not be usefull
    test -d "${path//\'/}" && $ionice rm -rf "${path//\'/}"
  else
    mysql_query "DELETE FROM mailbox WHERE id=$id AND mail_action='DELETING';"
  fi
done

# List the adresses to DELETE
# Delete if only if there isn't any mailbox refering to it
mysql_query "DELETE FROM a USING address a, mailbox m  WHERE (a.mail_action='DELETE' OR a.mail_action='DELETING') AND a.id != m.address_id;"

# Delete the lock
rm -f "$LOCK_FILE"
