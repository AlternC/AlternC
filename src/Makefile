# ----------------------------------------------------------------------
# AlternC - Web Hosting System
# Copyright (C) 2000-2012 by the AlternC Development Team.
# https://alternc.org/
# ----------------------------------------------------------------------
# LICENSE
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License (GPL)
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# To read the license please visit http://www.gnu.org/copyleft/gpl.html
# ----------------------------------------------------------------------
# Purpose of file: Makefile des binaires de /usr/lib/alternc
# ----------------------------------------------------------------------
SETUID=quota_edit quota_get mem_add mem_del du.pl
SCRIPTS=sqlbackup.sh quota_init quota_delete update_domains.sh slave_dns sendmail spoolsize.php fixperms.sh alternc-dboptimize export_account.php cron_users_doit.sh cron_users.sh compress_logs.sh delete_logs.sh quota-warning.sh update_mails.sh alternc_add_policy_dovecot rebuild_all_webconf.sh courier-dovecot-migrate.pl popimap-log-login.sh
LIBS=functions.sh functions_hosting.sh functions_dns.sh
BIN=$(DESTDIR)/usr/lib/alternc/

install: 
	# Group 1999 references alterncpanel
	chown root:1999 $(BIN)
	chmod 755 $(BIN)
	install -o root -g 1999 -m4750 $(SETUID) $(BIN)
	install -o root -g 1999 -m0750 $(SCRIPTS) $(BIN)
	install -o root -g 1999 -m0644 $(LIBS) $(BIN)
