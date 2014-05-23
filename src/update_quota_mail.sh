#!/bin/bash
. /usr/lib/alternc/functions.sh

#You can call this script either without arguments, inwich case each maildir quotas will be recalculated
#or you can call it with a directory reffering to a maildir to just sync one mailbox

#gerer les options : tout , 1boite , un domaine, un compte
while getopts "a:m:d:c:" optname
do
  case "$optname" in
  "a")
    maildirs=`find "$ALTERNC_MAIL/" -maxdepth 2 -mindepth 2 -type d`
  ;;
  "m")
    if [[ "$OPTARG" =~ ^[^\@]*@[^\@]*$ ]] ; then
      if [[ "$(mysql_query "select userdb_home from dovecot_view where user = '$OPTARG'")" ]]; then
        maildirs=$(mysql_query "select userdb_home from dovecot_view where user = '$OPTARG'")
      else
        echo "Bad mail provided"
      fi
    else
      echo "Bad mail provided"
    fi
  ;;
  "d")
    if [[ "$OPTARG" =~ ^[a-z\-]+(\.[a-z\-]+)+$ ]] ; then
      if [[ "$(mysql_query "select domaine from domaines where domaine = '$OPTARG'")" ]]; then
          maildirs=$(mysql_query "select userdb_home from dovecot_view where user like '%@$OPTARG'")
	    else
        echo "Bad domain provided"
      fi  
    else
      echo "Bad domain provided 2"
    fi
  ;;
  "c")
    if [[ "$OPTARG" =~ ^[a-z]*$ ]] ; then
      if [[ "$(mysql_query "select domaine from domaines where domaine = '$1'")" ]]; then
          maildirs=$(mysql_query "select userdb_home from dovecot_view where userdb_uid = $OPTARG")
      else
        echo "Bad account provided"
      fi
    else
      echo "Bad account provided"
    fi
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




#Then we loop through every maildir to get the maildir size
for i in $maildirs ; do

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

