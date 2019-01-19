#!/bin/bash
# Upgrading script to AlternC 1.1

CONFIG_FILE="/etc/alternc/local.sh"
PATH=/sbin:/bin:/usr/sbin:/usr/bin

umask 022

if [ ! -r "$CONFIG_FILE" ]; then
    echo "Can't access $CONFIG_FILE."
    exit 1
fi

if [ $(id -u) -ne 0 ]; then
    echo "3.0.0~2.sh must be launched as root"
    exit 1
fi

. "$CONFIG_FILE"

MAIL_DIR="$ALTERNC_LOC/mail"

## This part update mails' file owner and group ##
fix_mail() {
    read -r LOGIN GID || true
    while [ "$LOGIN" ]; do
        INITIALE=${LOGIN:0:1}
        MAIL=${LOGIN//@/_}
        REP="$ALTERNC_LOC/mail/$INITIALE/$MAIL/"
        chown --recursive "$GID":vmail "$REP"
        read -r LOGIN GID || true
    done
}

query="select user,userdb_gid from dovecot_view"
mysql --defaults-file=/etc/alternc/my.cnf --skip-column-names -B -e "$query" |fix_mail
## End of mails' files owner and group fixing part ##

## This part does the migration from Courier IMAP and POP3, preserving IMAP UIDs and POP3 UIDLs. ##
## It reads Courier's 'courierimapuiddb' and 'courierpop3dsizelist' files and produces 'dovecot-uidlist' file from it. ##
# We warn user it will take some time to migrate all indexes
echo -e "\033[31m"
echo "################################################"
echo "# /!\ CONVERTING COURIER INDEXES TO DOVECOT /!\ "
echo "# /!\        THIS MAY TAKE A WHILE !        /!\ "
echo "#                                               "
echo "# If you want to regenerate specifics indexes,  "
echo "# remove related 'dovecot-uidlist' files in     "
echo "#   $MAIL_DIR "
echo "# then execute this command manually :          "
echo "#                                               "
echo "# perl \"/usr/lib/alternc/courier-dovecot-migrate.pl\" --to-dovecot --convert --recursive \"$MAIL_DIR\""
echo "#                                               "
echo "# Add \"--overwrite\" option if you want to     "
echo "# overwrite ALL 'dovecot-uidlist' indexes       "
echo "################################################"
echo -e "\033[0m"


# Stoping dovecot service
invoke-rc.d dovecot stop || true

# We call the migration script (provided by wiki.dovecot.com)
perl "/usr/lib/alternc/courier-dovecot-migrate.pl" --to-dovecot --convert --recursive "$MAIL_DIR"


#We have to resync maildirs quotas with dovecot informations.
/usr/lib/alternc/update_quota_mail.sh

# Starting dovecot service
invoke-rc.d dovecot start || true
## End of migration part
