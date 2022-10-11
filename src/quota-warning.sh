#!/bin/bash

PERCENT="$1"
MAILUSER="$2"
DOM="$(echo "${MAILUSER}" | sed -e 's/.*@//')"
FROM="postmaster@$DOM"

cat <<EOF | /usr/lib/dovecot/deliver -d "${MAILUSER}" -o "plugin/quota=maildir:User quota:noenforcing"
From: $FROM
To: $MAILUSER
Subject: Your email quota is $PERCENT% full
Content-Type: text/plain; charset=UTF-8

Your mailbox is now $PERCENT% full."

EOF
exit 0
