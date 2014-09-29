#!/bin/bash

# this script is for 3.1.2 or 3.2.2
# we named it 3.4.0.sh since some of us had a 3.3.0~a.sql installed at some point in time
# which means the alternc_status table have this number in mind.
# so we need to have a bigger one 
. /etc/alternc/local.sh

echo "Fix OpenDKIM key generation"
/usr/lib/alternc/alternc_fix_opendkim.php

echo "Fix phpmyadmin special user"
/usr/lib/alternc/alternc_fix_myadm_users.php

echo "Fix of dovecot quotas"
/usr/lib/alternc/update_quota_mail.sh

DIDSOMETHING=1
while [ "$DIDSOMETHING" -gt 0 ]
do
    DIDSOMETHING=0
    for name in $(mysql --defaults-file=/etc/alternc/my.cnf --skip-column-names -B -e "SELECT name FROM variable GROUP BY name HAVING COUNT(*)>1;")
    do
	mysql --defaults-file=/etc/alternc/my.cnf -e "DELETE FROM variable WHERE name='$name' LIMIT 1"
	DIDSOMETHING=1
    done
done

# NOW we rollback the 3.3.0~a.sql crappy upgrade some of us had ...
for field in id strata strata_id type
do
    mysql --defaults-file=/etc/alternc/my.cnf -f -e "ALTER TABLE variable DROP $field;"
done
mysql --defaults-file=/etc/alternc/my.cnf -f -e "ALTER TABLE variable DROP PRIMARY KEY;"

mysql --defaults-file=/etc/alternc/my.cnf -f -e "ALTER TABLE variable DROP KEY name_2"
mysql --defaults-file=/etc/alternc/my.cnf -f -e "ALTER TABLE variable DROP KEY name"
mysql --defaults-file=/etc/alternc/my.cnf -f -e "ALTER TABLE variable ADD PRIMARY KEY name (name)"

# and we fix variable if needed : 
mysql --defaults-file=/etc/alternc/my.cnf -f -e "UPDATE variable SET value='$FQDN' WHERE value='%%FQDN%%'"

