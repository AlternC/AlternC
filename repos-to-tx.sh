#!/bin/bash

#------------------------------------------------------------
# Short doc: launch this after big changes, so that 
# people who translate the project have the new strings ;) 
#------------------------------------------------------------

# Long doc: 
# Take each sub-project of AlternC 
# (EXPECTED IN PARENT FOLDERS of alternc/trunk/)
# (yes, one day we will be united again ;) )
# and regenerate the .POT 
# then regenerate the .PO for each language
# then merge them into one big .po file and 
# put them in the lang/ folder
#
# finally, push en_US to transifex
# to be translated.


pushd ../..

langs="fr_FR de_DE en_US es_ES pt_BR" 

for project in alternc alternc-awstats alternc-mailman
do
    pushd "$project/trunk/bureau/locales"
    make
    popd
done

# no merge all the po's for each language
for lang in $langs
do
    sublang="`echo $lang | cut -c 1-2`"
    echo "doing lang $lang"
    rm -rf "alternc/trunk/tmp.$lang"
    mkdir "alternc/trunk/tmp.$lang"
    # po-debconf : (they are using only the language code, not lang_country
    if [ "$sublang" != "en" ] ; then
	cp "alternc/trunk/debian/po/${sublang}.po" "alternc/trunk/tmp.$lang/alternc.debconf.po"
	cp "alternc-mailman/trunk/debian/po/${sublang}.po" "alternc/trunk/tmp.$lang/alternc-mailman.debconf.po"
	cp "alternc-awstats/trunk/debian/po/${sublang}.po" "alternc/trunk/tmp.$lang/alternc-awstats.debconf.po"
    fi
    cp "alternc/trunk/bureau/locales/$lang/LC_MESSAGES/messages.po" \
	"alternc/trunk/bureau/locales/$lang/LC_MESSAGES/manual.po" \
	"alternc-mailman/trunk/bureau/locales/$lang/LC_MESSAGES/mailman.po" \
	"alternc-mailman/trunk/bureau/locales/$lang/LC_MESSAGES/mailman_manual.po" \
	"alternc-awstats/trunk/bureau/locales/$lang/LC_MESSAGES/aws.po" \
	"alternc-awstats/trunk/bureau/locales/$lang/LC_MESSAGES/aws_manual.po" \
	"alternc/trunk/tmp.$lang/" 
    # now we have all .po files in one folder, merge them into one big catalog: 
    msgcat --use-first -o "alternc/trunk/lang/${lang}.po" alternc/trunk/tmp.$lang/*
    rm -rf "alternc/trunk/tmp.$lang"
   echo "done"
done

# Now pushing po files into transifex website:
cd alternc/trunk/lang/
tx push -s 

popd
