#!/bin/bash
. /usr/lib/alternc/functions.sh

#You can call this script either without arguments, inwich case each maildir quotas will be recalculated
#or you can call it with a directory reffering to a maildir to just sync one mailbox

function showhelp() {
  echo "FIXME: some help"
  exit
}

# FIXME: storing THAT amount of data in MAILDIRS (on BIG install like Lautre.net) may crash the shell?

# Generate the $maildirs list based on the arguments
while getopts "am:d:c:" optname
do
  case "$optname" in
  "a")
    # All mails
    maildirs=$(mysql_query "select userdb_home from dovecot_view order by 1")  
  ;;
  "m")
    # An email
    if [[ "$OPTARG" =~ ^[^\@]*@[^\@]*$ ]] ; then
      if [[ "$(mysql_query "select userdb_home from dovecot_view where user = '$OPTARG'")" ]]; then
        maildirs=$(mysql_query "select userdb_home from dovecot_view where user = '$OPTARG' order by 1")
      else
        echo "Bad mail provided"
        showhelp
      fi
    else
      echo "Bad mail provided"
      showhelp
    fi
  ;;
  "d")
    # Expecting a domain

    # Check if domain is well-formed
    if [[ ! "$OPTARG" =~ ^[a-z\-]+(\.[a-z\-]+)+$ ]] ; then
      echo "Bad domain provided"
      showhelp
    fi

    # Attemp to get from database.
    if [[ ! "$(mysql_query "select domaine from domaines where domaine = '$OPTARG'")" ]]; then
      # Seem to be empty
      echo "Bad domain provided"
      showhelp
    fi  

    maildirs=$(mysql_query "select userdb_home from dovecot_view where user like '%@$OPTARG' order by 1")
  ;;
  "c")
    # An account
    if [[ "$OPTARG" =~ ^[a-z]*$ ]] ; then
      if [[ "$(mysql_query "select domaine from domaines where domaine = '$1'")" ]]; then
          maildirs=$(mysql_query "select userdb_home from dovecot_view where userdb_uid = $OPTARG order by 1")
      else
        echo "Bad account provided"
        showhelp
      fi
    else
      echo "Bad account provided"
      showhelp
    fi
  ;;
  "?")
    echo "Unknown option $OPTARG - stop processing"
    showhelp
    exit
  ;;
  ":")
    echo "No argument value for option $OPTARG - stop processing"
    showhelp
    exit
  ;;
  *)
    # Should not occur
    echo "Unknown error while processing options"
    showhelp
    exit
  ;;
  esac
done

# Now we have $maildirs, we can work on it

# FIXME add check if maildir is empty

#Then we loop through every maildir to get the maildir size
for i in $maildirs ; do

	if [ ! -d "$i" ];then
	  echo "The maildir $i does not exists. It's quota won't be resync"
    continue
  fi

	# We grep only mails, not the others files
	mails=`find $i -type f | egrep "(^$i)*[0-9]+\.M"`

	# This part count the total mailbox size (mails + sieve scripts + ...)
	size=`du -b -s $i|awk '{print $1}'` 

	mail_count=`echo $mails|wc -w` 
	echo "folder : "$i
	echo "mail count : "$mail_count
	echo "dir size : "$size
	echo ""
	#update the mailbox table accordingly
	MAILADD=`basename $i`
	MAILADD=${MAILADD/_/@}
	mysql_query "REPLACE INTO dovecot_quota VALUES('$MAILADD', $size, $mail_count);"
done

# may cause a problem, let's fix this here :) 
mysql_query "UPDATE mailbox SET quota=0 WHERE quota IS NULL;"
