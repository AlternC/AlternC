#!/bin/bash

# Migrate a repository to WHEEZY

# DON'T COMMIT ANYTHING AFTER launching this
# reset your subversion repos back to the serverside one!

patch -p0 < stretch/quota_init.diff

cd `dirname $0`

pushd ../debian
patch <../stretch/control.diff
patch <../stretch/changelog.diff
popd

cp ../jessie/vhost.conf ../etc/alternc/templates/apache2/
cp ../jessie/bureau.conf ../etc/alternc/templates/alternc/
cp ../jessie/alternc.install ../install/
cp ../jessie/apache2.conf ../etc/alternc/templates/alternc/
# alternc-roundcube package :
cp ../jessie/roundcube.config.inc.php ../roundcube/templates/roundcube/config.inc.php
rm ../roundcube/templates/roundcube/main.inc.php
cp ../jessie/roundcube.password.config.inc.php ../roundcube/templates/roundcube/plugins/password/config.inc.php
cp ../jessie/roundcube-install ../roundcube/
cp ../jessie/alternc-roundcube.postinst ../debian/

# alternc-ssl package :
cp ../jessie/ssl.conf ../ssl/
cp ../jessie/alternc-ssl.install.php ../ssl/
cp opendkim.conf ../etc/alternc/templates/opendkim.conf
