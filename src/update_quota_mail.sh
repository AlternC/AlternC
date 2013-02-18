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
  d=`find "$ALTERNC_MAIL/" -maxdepth 2 -mindepth 2 -type d`
fi



#Then we loop through every maildir to get the maildir size
for i in $d ; do

	if [ -d "$i" ];then
	  user=`ls -l $i| tail -n 1|cut -d' ' -f 3` 
	  # We grep only mails, not the others files
	  mails=`find $i -type f | egrep "(^$i)*[0-9]+\.M"`

	  # This part only count mails size
	  #size=0
	  #for j in $mails
	  #do
	  #	size=$(( $size + `du -b $j|awk '{print $1}'`))
	  #done

	  # This part count the total mailbox size (mails + sieve scripts + ...)
	  size=`du -b -s $i|awk '{print $1}'` 

	  mail_count=`echo $mails|wc -w` 
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

