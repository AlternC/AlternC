#!/bin/bash

PERCENT=$1
DOM="`echo $USER | sed -e 's/.*@//'`"
FROM="postmaster@$DOM"

msg="From: $FROM
To: $USER
Subject: Your email quota is $PERCENT% full
Content-Type: text/plain; charset=UTF-8

Your mailbox is now $PERCENT% full."

echo -e "$msg" | /usr/sbin/sendmail -f $FROM "$USER"

exit 0
