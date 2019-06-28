
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin

# Create /run/ folder : it may be a ramdrive
@reboot	 root	   mkdir -p /run/alternc && chown alterncpanel:alterncpanel /run/alternc 

# Every 2 days compress log files
30 4 * * *	alterncpanel	/usr/lib/alternc/compress_logs.sh

# Suppress log files older than one year
0 4 * * *	alterncpanel	/usr/lib/alternc/delete_logs.sh

# Every day at 5am and every week at 4am, make requested SQL backups
0 5 * * *	alterncpanel	/usr/lib/alternc/sqlbackup.sh -t daily
0 4 * * 0	alterncpanel	/usr/lib/alternc/sqlbackup.sh -t weekly

# Every minute, spool waiting domain changes
* * * * *	root		/usr/lib/alternc/update_domains.php

# Every 5 minutes, do mails actions
*/5 * * * *	root		/usr/lib/alternc/update_mails.sh

# Every hour, check for slave_dns refreshes
5 * * * *	root            /usr/lib/alternc/slave_dns

# Every day at 2am, compute web, mailman and db space usage per account.
# You may put this computing every week only or on your filer on busy services.
0 2 * * *	alterncpanel 	/usr/lib/alternc/spoolsize.php 2>&1 > /dev/null

# Once a week at 7am, optimise the AlternC database
0 1 * * 7	alterncpanel  	/usr/lib/alternc/alternc-dboptimize

# Every 30 minutes, do cron_users actions
00,30 * * * *	alterncpanel	/usr/lib/alternc/cron_users.sh

# Every 20 minutes, do actions
*/20 * * * *	root	/usr/lib/alternc/do_actions.php

# Calculate the mail accounts size once a week beacause the dovecot plugin to do that is not precise (see ticket AlternC #168)
# Every Sunday at 4am
0 4 * * 0	root	/usr/lib/alternc/update_quota_mail.sh -a

# every minute, search for new certificates
* * * * *       root    /usr/lib/alternc/cron_ssl.sh

