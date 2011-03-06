#!/bin/bash
. /etc/alternc/local.sh

AWK=/usr/bin/awk
DF=/bin/df
SED=/bin/sed
MOUNT=/bin/mount
QUOTA=/usr/bin/quota
GREP=/bin/grep
WC=/usr/bin/wc

DATA_PART=`$DF ${ALTERNC_LOC} 2>/dev/null | $AWK '/^\// { print $1 }'`

# quota will give over NFS will print the partition using the full NFS name
# (e.g. 10.0.0.1:/var/alternc) so we need to lookup first with mount
# to convert DATA_PART if needed.
QUOTA_PART=`$MOUNT | $SED -n -e "s,\([^ ]*\) on ${DATA_PART} type nfs.*,\1,p"`
if [ -z "$QUOTA_PART" ]; then
    QUOTA_PART="$DATA_PART"
fi

# quota will split its display on two lines if QUOTA_PART is bigger than 15
# characters. *sigh*
PART_LEN=`echo -n "$QUOTA_PART" | $WC -c`
if [ "$PART_LEN" -gt 15 ]; then
    $QUOTA -g "$1" |
       $SED -n -e "\\;${QUOTA_PART};,+1s/ *\([0-9]*\) .*/\1/p" |
       $GREP -v '^$'
    $QUOTA -g "$1" |
       $SED -n -e "\\;${QUOTA_PART};,+1s/ *[0-9]* *\([0-9]*\) .*/\1/p" |
       $GREP -v '^$'
else
    $QUOTA -g "$1" | $AWK /${QUOTA_PART//\//\\\/}/\ {print\ '$2'}
    $QUOTA -g "$1" | $AWK /${QUOTA_PART//\//\\\/}/\ {print\ '$3'}
fi

