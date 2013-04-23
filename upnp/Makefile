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
# Purpose of file: Install the files of alternc-upnp packages
# ----------------------------------------------------------------------

install:
# Desktop files
	install -m 644 m_upnp.php $(DESTDIR)/usr/share/alternc/panel/class/
	install -m 644 upnp_list.php upnp_change.php $(DESTDIR)/usr/share/alternc/panel/admin/
	install -m 644 adminmenu_upnp.php $(DESTDIR)/usr/share/alternc/panel/admin/
# Cron
	install -m 755 update_upnp.sh $(DESTDIR)/usr/lib/alternc/
# Install script
	install -m 644 upnp.sql $(DESTDIR)/usr/share/alternc/install/
	install -m 755 upnp-install $(DESTDIR)/usr/lib/alternc/install.d
