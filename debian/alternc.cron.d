# Every day at 4am, produce raw statistics
0 4 * * *	www-data	/usr/lib/alternc/rawstat.daily

# Every day at 5am and every week at 4am, make requested SQL backups
0 5 * * *	www-data	/usr/lib/alternc/sqlbackup.sh daily
0 4 * * 0	www-data	/usr/lib/alternc/sqlbackup.sh weekly

# Every 5 minutes, spool waiting domain changes
*/5 * * * *	root		/usr/lib/alternc/update_domains.sh

# Every hour, check for slave_dns refreshes
5 * * * *       root            /usr/lib/alternc/slave_dns
