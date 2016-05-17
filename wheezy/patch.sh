#!/bin/bash

# Migrate a repository to WHEEZY

# DON'T COMMIT ANYTHING AFTER launching this
# reset your subversion repos back to the serverside one!

cd `dirname $0` 

pushd ../debian 
patch <../wheezy/control.diff
patch <../wheezy/changelog.diff
popd
pushd ../install
patch <../wheezy/alternc.install.diff
popd

cp -vf alternc-dict-quota.conf  alternc-sql.conf ../etc/alternc/templates/dovecot/
mkdir -p ../etc/alternc/templates/dovecot/conf.d/
cp -vf 95_alternc.conf ../etc/alternc/templates/dovecot/conf.d/

pushd ../etc/alternc/templates/dovecot
rm -vf dovecot.conf  dovecot-dict-quota.conf dovecot-sql.conf
popd



