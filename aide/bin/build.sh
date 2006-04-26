#!/bin/sh

# $Id: build.sh,v 1.1.1.1 2003/03/26 17:41:29 root Exp $ 

cd /data/bureau/admin/aide/
# BUILD HTML
rm -rf html
mkdir html
docbook2html --dsl /data/bureau/admin/aide/bin/frames.dsl -o html index.sgml
rsync files/ html/files/ -a
rsync inline/ html/ -a
cd ..

# Construction du fichier langue : 
# fr_FR : 
cd /data/bureau/admin/locale/fr_FR/LC_MESSAGES
msgfmt -o alternc.mo alternc.po
cd /data/bureau/admin/locale/en_US/LC_MESSAGES
msgfmt -o alternc.mo alternc.po

