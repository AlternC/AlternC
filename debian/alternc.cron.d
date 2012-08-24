# Every day at 4am, produce raw statistics
0 4 * * *	root	/usr/lib/alternc/rawstat.daily

# Every 2 days compress log files
0 4 * * *	alterncpanel	/usr/lib/alternc/compress_logs.sh

# Suppress log files older than one year
0 4 * * *	alterncpanel	/usr/lib/alternc/delete_logs.sh

# Every day at 5am and every week at 4am, make requested SQL backups
0 5 * * *	alterncpanel	/usr/lib/alternc/sqlbackup.sh -t daily
0 4 * * 0	alterncpanel	/usr/lib/alternc/sqlbackup.sh -t weekly

# Every 5 minutes, spool waiting domain changes
*/5 * * * *	root		/usr/lib/alternc/update_domains.sh

# Every hour, check for slave_dns refreshes
5 * * * *       root            /usr/lib/alternc/slave_dns

# Every day at 2am, compute web, mail and db space usage per account.
# You may put this computing every week only or on your filer on busy services.
0 2 * * *   alterncpanel /usr/lib/alternc/spoolsize.php

# Once a week at 7am, optimise the AlternC database
0 1 * * 7   alterncpanel  /usr/lib/alternc/alternc-dboptimize

# We change the session directory... debian's patch modified :
# Look for and purge old sessions every 30 minutes
09,39 *     * * *     root   [ -x /usr/lib/php5/maxlifetime ] && [ -d /var/lib/php5 ] && find /var/alternc/sessions/ -type f -cmin +$(/usr/lib/php5/maxlifetime) -delete

