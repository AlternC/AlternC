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
# Purpose of file: Install the files of alternc-roundcube packages
# ----------------------------------------------------------------------

install:
	# alternc-roundcube files install
	install -m 644 templates/roundcube/config.inc.php $(DESTDIR)/etc/alternc/templates/roundcube/
	install -m 644 templates/roundcube/plugins/password/config.inc.php $(DESTDIR)/etc/alternc/templates/roundcube/plugins/password/
	install -m 644 templates/roundcube/plugins/managesieve/config.inc.php $(DESTDIR)/etc/alternc/templates/roundcube/plugins/managesieve/
	install -m 644 roundcube_alternc_logo.png $(DESTDIR)/usr/share/roundcube/skins/default/images/
	install -m 750 roundcube-install $(DESTDIR)/usr/lib/alternc/install.d/
	# domaintype template: 
	install -m 644 templates/apache2/roundcube.conf $(DESTDIR)/etc/alternc/templates/apache2/
	# Desktop files
	install -o 1999 -g 1999 -m 644 class/m_roundcube.php $(DESTDIR)/usr/share/alternc/panel/class/
