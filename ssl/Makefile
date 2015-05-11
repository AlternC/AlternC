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
# Purpose of file: Install the files of alternc-ssl packages
# ----------------------------------------------------------------------

install:
	install -m 0644 -g root -o root ssl.sql $(DESTDIR)/usr/share/alternc/install/
	install -m 0755 -g root -o root alternc-ssl.install.php $(DESTDIR)/usr/lib/alternc/install.d/alternc-ssl
	install -m 0644 -g root -o root README.txt $(DESTDIR)/var/lib/alternc/ssl/private/
	install -m 0644 -g root -o root apache2.conf $(DESTDIR)/etc/apache2/conf.d/alternc-ssl-alias.conf

# incron
	install -m 0755 -g root -o root ssl_alias_manager.sh $(DESTDIR)/usr/lib/alternc/
	install -m 0644 -g root -o root alternc-ssl.incron.d $(DESTDIR)/etc/incron.d/alternc-ssl

	install -m 0644 -g 1999 -o root panel/class/m_ssl.php $(DESTDIR)/usr/share/alternc/panel/class/
	install -m 0644 -g 1999 -o root panel/admin/*.php $(DESTDIR)/usr/share/alternc/panel/admin/
	install -m 0644 -g 1999 -o root panel/admin/images/* $(DESTDIR)/usr/share/alternc/panel/admin/images/
# locales
	install -m 0644 -g 1999 -o root panel/locales/pt_BR/LC_MESSAGES/ssl.po $(DESTDIR)/usr/share/alternc/panel/locales/pt_BR/LC_MESSAGES/ssl.po
	install -m 0644 -g 1999 -o root panel/locales/fr_FR/LC_MESSAGES/ssl.po $(DESTDIR)/usr/share/alternc/panel/locales/fr_FR/LC_MESSAGES/ssl.po
	install -m 0644 -g 1999 -o root panel/locales/en_US/LC_MESSAGES/ssl.po $(DESTDIR)/usr/share/alternc/panel/locales/en_US/LC_MESSAGES/ssl.po
	install -m 0644 -g 1999 -o root panel/locales/de_DE/LC_MESSAGES/ssl.po $(DESTDIR)/usr/share/alternc/panel/locales/de_DE/LC_MESSAGES/ssl.po
	install -m 0644 -g 1999 -o root panel/locales/it_IT/LC_MESSAGES/ssl.po $(DESTDIR)/usr/share/alternc/panel/locales/it_IT/LC_MESSAGES/ssl.po
	install -m 0644 -g 1999 -o root panel/locales/nl_NL/LC_MESSAGES/ssl.po $(DESTDIR)/usr/share/alternc/panel/locales/nl_NL/LC_MESSAGES/ssl.po
	install -m 0644 -g 1999 -o root panel/locales/es_ES/LC_MESSAGES/ssl.po $(DESTDIR)/usr/share/alternc/panel/locales/es_ES/LC_MESSAGES/ssl.po
# FIXME: how can we do that properly ??
#	install -m 0644 -g 1999 -o root panel/admin/styles/* $(DESTDIR)/usr/share/alternc/panel/admin/styles/

	install -m 0644 -g 1999 -o root templates/* $(DESTDIR)/etc/alternc/templates/apache2/
	install -m 0644 -g 1999 -o root ssl.conf $(DESTDIR)/etc/alternc/templates/apache2/mods-available/
	install -m 0644 -g 1999 -o root openssl.cnf $(DESTDIR)/etc/alternc/
	install -m 0755 -g 1999 -o root hosting_vhost-ssl.sh $(DESTDIR)/etc/alternc/functions_hosting/
	ln -s hosting_vhost-ssl.sh $(DESTDIR)/etc/alternc/functions_hosting/hosting_panel-ssl.sh
	ln -s hosting_vhost-ssl.sh $(DESTDIR)/etc/alternc/functions_hosting/hosting_vhost-mixssl.sh
	ln -s hosting_vhost-ssl.sh $(DESTDIR)/etc/alternc/functions_hosting/hosting_roundcube-ssl.sh
	ln -s hosting_vhost-ssl.sh $(DESTDIR)/etc/alternc/functions_hosting/hosting_squirrelmail-ssl.sh
	ln -s hosting_vhost-ssl.sh $(DESTDIR)/etc/alternc/functions_hosting/hosting_php52-ssl.sh
	ln -s hosting_vhost-ssl.sh $(DESTDIR)/etc/alternc/functions_hosting/hosting_php52-mixssl.sh
	ln -s hosting_vhost-ssl.sh $(DESTDIR)/etc/alternc/functions_hosting/hosting_url-ssl.sh
# api
	install -m 0644 -g root -o root api/Ssl.php $(DESTDIR)/usr/share/php/Alternc/Api/Object/
