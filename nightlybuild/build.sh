#!/bin/bash

VERSION="1.0"
# We launch this script inside the home directory of the "nigthly build user"
cd ~ 

# Shall we build or not ? 
links -dump "http://alternc.org/svn/" | head -1 >new
if [ "`cat new`" == "`cat old`" ]
then
    echo "No need to build : no change in the source since last launch"
    exit 0
fi
mv -f new old

DATE="`date +%Y%m%d%H%M`"

mkdir ~/www/$DATE

echo "BuildRoot cleanup"
cd ~/buildroot/ && rm -rf * 

echo "Building AlternC"
svn export -q http://alternc.org/svn/alternc/trunk/ alternc
cd alternc
mv debian/changelog debian/changelog.orig
cat >debian/changelog <<EOF
alternc (${VERSION}~nightly${DATE}) stable; urgency=low
  * Automatic Nightly build of AlternC
  * `cat ~/old`
    
 -- Nightly Build <nightly@alternc.org>  `date -R`

EOF
cat debian/changelog.orig >>debian/changelog
debuild -k0x1994905A >/dev/null
cd ..
rm -rf alternc 
mv *.deb *.dsc *.build *.changes *.tar.gz ~/www/$DATE

for module in awstats mailman changepass procmail
  do
  echo "Building AlternC-${module}"
  svn export -q http://alternc.org/svn/alternc-${module}/trunk/ alternc-${module}
  cd alternc-${module}
  mv debian/changelog debian/changelog.orig
  cat >debian/changelog <<EOF
alternc-${module} (${VERSION}~nightly${DATE}) stable; urgency=low
  * Automatic Nightly build of AlternC-${module}
  * `cat ~/old`
    
 -- Nightly Build <nightly@alternc.org>  `date -R`

EOF
  cat debian/changelog.orig >>debian/changelog
  debuild -k0x1994905A >/dev/null
  cd ..
  rm -rf alternc-${module}
  mv *.deb *.dsc *.build *.changes *.tar.gz ~/www/$DATE
done

rm ~/www/latest
ln -sf ~/www/$DATE ~/www/latest

cd ~/www
apt-ftparchive packages $DATE/ | tee $DATE/Packages | gzip -c9 >$DATE/Packages.gz 
(echo "Suite: $DATE"
echo "Codename: latest"
echo "Components: main"
echo "Origin: AlternC"
echo "Label: AlternC Nightly Build dated $DATE"
echo "Architectures: i386 amd64"
echo "Description: This repository contains a nightly build of all AlternC packages"
apt-ftparchive release $DATE/ ) >$DATE/Release   

gpg -ba $DATE/Release
mv $DATE/Release.asc $DATE/Release.gpg

