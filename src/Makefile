#
# $Id: Makefile,v 1.20 2005/05/21 16:07:32 arnaud-lb Exp $
# ----------------------------------------------------------------------
# AlternC - Web Hosting System
# Copyright (C) 2002 by the AlternC Development Team.
# http://alternc.org/
# ----------------------------------------------------------------------
# Based on:
# Valentin Lacambre's web hosting softwares: http://altern.org/
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
#
SETUID=quota_edit quota_get mem_add mem_del du.pl
SCRIPTS=sqlbackup.sh rawstat.daily quota_init quota_delete update_domains.sh slave_dns sendmail spoolsize.php fixperms.sh alternc-dboptimize export_account.php cron_users_doit.sh cron_users.sh compress_logs.sh delete_logs.sh quota-warning.sh update_mails.sh postfix-add-policy
LIBS=functions.sh functions_hosting.sh functions_dns.sh
BIN=$(DESTDIR)/usr/lib/alternc/

install: 
	# Group 1999 references alterncpanel
	chown root:1999 $(BIN)
	chmod 755 $(BIN)
	install -o root -g 1999 -m4750 $(SETUID) $(BIN)
	install -o root -g 1999 -m0750 $(SCRIPTS) $(BIN)
	install -o root -g 1999 -m0644 $(LIBS) $(BIN)
