#!/bin/bash

# Take each sub-project of AlternC 
# (EXPECTED IN PARENT FOLDERS)
# (yes, one day we will be united again ;) )
# and get the TRANSLATED strings from transifex
# then put them at the right places in the repositories
# and commit everything using svn

tx pull -a 

langs="fr_FR de_DE en_US es_ES pt_BR" 

for lang in $langs
do
    echo "doing lang $lang"
    cp "lang/${lang}.po" "bureau/locales/$lang/LC_MESSAGES/alternc"
    sublang="`echo $lang | cut -c 1-2`"
    # merge the po for debconf into the relevant file for the modules : 
    if [ "$lang" != "en_US" ]
    then
	msgcat --use-first --less-than=3 --more-than=1 -o tmp.po  "lang/${lang}.po" "debian/po/${sublang}.po"
	mv -f tmp.po "debian/po/${sublang}.po"
	msgcat --use-first --less-than=3 --more-than=1 -o tmp.po  "lang/${lang}.po" "../../alternc-awstats/trunk/debian/po/${sublang}.po"
	mv -f tmp.po "../../alternc-awstats/trunk/debian/po/${sublang}.po"
	msgcat --use-first --less-than=3 --more-than=1 -o tmp.po  "lang/${lang}.po" "../../alternc-mailman/trunk/debian/po/${sublang}.po"
	mv -f tmp.po "../../alternc-mailman/trunk/debian/po/${sublang}.po"
    fi
    echo "done"
done

# Now committing 
svn commit -m "Updating language files from Transifex"
pushd ../../alternc-mailman/trunk
svn commit -m "Updating language files from Transifex"
cd ../../alternc-awstats/trunk
svn commit -m "Updating language files from Transifex"
popd

