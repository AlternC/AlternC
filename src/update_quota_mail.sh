#! /bin/bash

. /usr/lib/alternc/functions.sh

#Fist we set the quotas no 0 (infinite for each already existing account
t=`mysql_query "UPDATE mailbox SET quota='0' WHERE quota IS NULL"`


#Then we loop through every maildir to get the maildir size
for i in $ALTERNC_LOC/mail/*/* ; do

	user=`ls -l $i| tail -n 1|cut -d' ' -f 3` 
	size=`du -s $i|awk '{print $1}'`
	q=`mysql_query "SELECT * FROM mailbox where path = '$i' "` 

	#when counting mails we eclude dovecot specific files
	mail_count=`find $i -type f |grep -v dovecot* |wc -l`
	echo "folder : "$i
	echo "mail count : "$mail_count
	echo "dir size : "$size
	echo ""

	if [ -z "$q" ]; then
		echo "no mail folder found for user $user "
	else
		#update the mailbox table accordingly
		q=`mysql_query "UPDATE mailbox SET bytes=$size WHERE path='$i' "` 
		q=`mysql_query "UPDATE mailbox SET messages=$mail_count WHERE path='$i' "` 
	fi 
done

