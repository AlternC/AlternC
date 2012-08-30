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
# Purpose of file: Install the files of alternc-squirrelmail packages
# ----------------------------------------------------------------------

install:
	# alternc-squirrelmail files install
	install -m 644 templates/squirrelmail/* $(DESTDIR)/etc/alternc/templates/squirrelmail/
	install -m 644 templates/javascript-common/* $(DESTDIR)/etc/alternc/templates/javascript-common/
	install -m 750 squirrelmail-install $(DESTDIR)/usr/lib/alternc/install.d/
	# domaintype template: 
	install -m 644 templates/apache2/squirrelmail.conf $(DESTDIR)/etc/alternc/templates/apache2/squirrelmail.conf
	# Desktop files
	install -o 1999 -g 1999 -m 644 class/m_squirrelmail.php $(DESTDIR)/var/alternc/bureau/class/
