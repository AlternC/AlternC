# Every day at 4am, produce raw statistics
0 4 * * *	root	/usr/lib/alternc/rawstat.daily

# Every day at 5am and every week at 4am, make requested SQL backups
0 5 * * *	www-data	/usr/lib/alternc/sqlbackup.sh daily
0 4 * * 0	www-data	/usr/lib/alternc/sqlbackup.sh weekly

# Every 5 minutes, spool waiting domain changes
*/5 * * * *	root		/usr/lib/alternc/update_domains.sh

# Every hour, check for slave_dns refreshes
5 * * * *       root            /usr/lib/alternc/slave_dns

# Every day at 2am, compute web, mail and db space usage per account.
# You may put this computing every week only or on your filer on busy services.
0 2 * * *       www-data 	/usr/lib/alternc/spoolsize.php

# alternc-specific PHP sessions cleanup routine
# copied from php5's
09,39 *     * * *     root   [ -d /var/alternc/sessions ] && find /var/alternc/sessions/ -type f -cmin +$(if [ -e /usr/lib/php4/maxlifetime ] ; then /usr/lib/php4/maxlifetime ; else /usr/lib/php5/maxlifetime ; fi) -print0 | xargs -r -0 rm
