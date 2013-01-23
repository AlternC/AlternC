#!/bin/bash
. /usr/lib/alternc/functions.sh

#You can call this script either without arguments, inwich case each maildir quotas will be recalculated
#or you can call it with a directory reffering to a maildir to just sync one mailbox

#basic checks
if [ $# -gt 1 ]; then
  echo "usage : update_quota_mail.sh (Maildir)."
  exit
fi

if [ $# -eq 1 ];then
  if [ ! -d "$1" ];then
    echo "$1 is not a directory, aborting."
    exit
  else
    d="$1"
  fi
else
  #Fist we set the quotas no 0 (infinite for each already existing account
  t=`mysql_query "UPDATE mailbox SET quota='0' WHERE quota IS NULL"`
  d="$ALTERNC_LOC/mail/*/*"
fi



#Then we loop through every maildir to get the maildir size
for i in $d ; do

	if [ -d "$i" ];then
	  user=`ls -l $i| tail -n 1|cut -d' ' -f 3` 
	  size=`du -s $i|awk '{print $1}'`

	  #when counting mails we exclude specific files
	  mail_count=`find $i  -type f -printf "%f\n"| egrep '^[0-9]+\.M'|wc -w` 
	  echo "folder : "$i
	  echo "mail count : "$mail_count
	  echo "dir size : "$size
	  echo ""
		#update the mailbox table accordingly
		mysql_query "UPDATE mailbox SET bytes=$size WHERE path='$i'  "
		mysql_query "UPDATE mailbox SET messages=$mail_count WHERE path='$i' " 
  else
	  echo "The maildir $i does not exists. It's quota won't be resync"
  fi
done

