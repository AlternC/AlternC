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
# Purpose of file: Install the files of alternc-awstats packages
# ----------------------------------------------------------------------

install:
	install -m 0644 -g root -o root alterncawstats.conf $(DESTDIR)/etc/alternc/
	install -m 0755 -g root -o root logresolvemerge.pl $(DESTDIR)/usr/lib/alternc/
	install -m 0644 -g 1999 -o root awstats_alternc.png $(DESTDIR)/usr/share/awstats/icon/other/
	install -m 0755 -g 1999 -o root alternc-awstats $(DESTDIR)/usr/lib/alternc/
	install -m 0755 -g 1999 -o root awstats.cache.php $(DESTDIR)/usr/lib/alternc/
	install -m 0644 awstats.sql $(DESTDIR)/usr/share/alternc/
	install -m 0644 -g 1999 -o root awstats.template.conf $(DESTDIR)/etc/alternc/templates/awstats/
	install -m 0644 -g 1999 -o root awstats.alternc.generic.conf $(DESTDIR)/etc/alternc/
	install -m 0644 -g 1999 -o root awstats.alternc.generic.conf $(DESTDIR)/etc/alternc/
	install -m 0644 -g 1999 -o root bureau/class/m_aws.php $(DESTDIR)/usr/share/alternc/panel/class/
	install -m 0644 -g 1999 -o root bureau/admin/* $(DESTDIR)/usr/share/alternc/panel/admin/

	#install -m 0444 lintian-override $(DESTDIR)/usr/share/lintian/overrides/alternc-awstats
