#!/bin/bash

# this script is for 3.1.2 or 3.2.2
# we named it 3.4.0.sh since some of us had a 3.3.0~rc1.sh installed at some point in time
# which means the alternc_status table have this number in mind.
# so we need to have a bigger one 

echo "Fix OpenDKIM key generation"
/usr/lib/alternc/alternc_fix_opendkim.php

echo "Fix phpmyadmin special user"
/usr/lib/alternc/alternc_fix_myadm_users.php

echo "Fix of dovecot quotas"
/usr/lib/alternc/update_quota_mail.sh
