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
# Purpose of file: Install the files of alternc-api package
# ----------------------------------------------------------------------

install:
	install -m 0644 -g root -o root api.conf $(DESTDIR)/etc/alternc/apache-panel.d
	install -m 0644 api.sql $(DESTDIR)/usr/share/alternc/
	install -m 0644 -g 1999 -o root panel/* $(DESTDIR)/usr/share/alternc/panel/admin/api/
	install -m 0644 ../lib/Alternc/Api/*.php $(DESTDIR)/usr/share/php/Alternc/Api/
	install -m 0644 ../lib/Alternc/Api/Auth/*.php $(DESTDIR)/usr/share/php/Alternc/Api/Auth/

