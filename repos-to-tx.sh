#!/bin/bash

#------------------------------------------------------------
# Short doc: launch this after big changes, so that
# people who translate the project have the new strings ;)
#------------------------------------------------------------

# USE IT INSIDE alternc/ folder which MUST BE a git clone
#
# Long doc:
# Take each sub-project of AlternC
# (EXPECTED IN PARENT FOLDERS from here)
# and regenerate the .POT
# then regenerate the .PO for each language
# then merge them into one big .po file and
# put them in the lang/ folder
#
# finally, push en_US to transifex
# to be translated.

hash tx 2>/dev/null || { echo >&2 "tx is required. Retrieve it from https://github.com/transifex/cli . Stopped."; exit 1; }


pushd debian
echo "Update of PO files in debian/"
debconf-updatepo
popd

pushd ..

langs="fr_FR de_DE en_US es_ES pt_BR it_IT nl_NL"

# external repositories :
for project in alternc alternc-mailman
do
    pushd "$project/bureau/locales"
    make
    popd
done

# internal po files :
for subproject in awstats
do
    pushd "alternc/$subproject/bureau/locales"
    make
    popd
done

# now merge all the po's for each language
for lang in $langs
do
    sublang="`echo $lang | cut -c 1-2`"
    echo "doing lang $lang"
    rm -rf "alternc/tmp.$lang"
    mkdir "alternc/tmp.$lang"
    # po-debconf : (they are using only the language code, not lang_country
#    if [ "$sublang" != "en" ] ; then
	cp "alternc/debian/po/${sublang}.po" "alternc/tmp.$lang/alternc.debconf.po"
	cp "alternc-mailman/debian/po/${sublang}.po" "alternc/tmp.$lang/alternc-mailman.debconf.po"
#	cp "alternc/trunk/awstats/debian/po/${sublang}.po" "alternc/trunk/tmp.$lang/alternc-awstats.debconf.po"
#    else
#	cp "alternc/trunk/debian/po/templates.pot" "alternc/trunk/tmp.$lang/alternc.debconf.po"
#	cp "alternc-mailman/trunk/debian/po/templates.pot" "alternc/trunk/tmp.$lang/alternc-mailman.debconf.po"
#    fi
    cp "alternc/bureau/locales/$lang/LC_MESSAGES/messages.po" \
	"alternc-mailman/bureau/locales/$lang/LC_MESSAGES/mailman.po" \
	"alternc/awstats/bureau/locales/$lang/LC_MESSAGES/aws.po" \
	"alternc/tmp.$lang/"
    # now we have all .po files in one folder, merge them into one big catalog:
    msgcat --use-first -o "alternc/lang/${lang}.po" alternc/tmp.$lang/*
    rm -rf "alternc/tmp.$lang"
   echo "done"
done

# Now pushing po files into transifex website:
cd alternc/lang/
tx push -s

popd
