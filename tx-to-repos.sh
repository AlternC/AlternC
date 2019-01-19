#!/bin/bash

#------------------------------------------------------------
# Short doc: launch this when people said they translated 
# the program in Transifex, so that their translation appears
# in the production package.
#------------------------------------------------------------

# USE IT INSIDE alternc/ folder which MUST BE a git clone
# 
# Long doc: 
# Take each sub-project of AlternC 
# (EXPECTED IN PARENT FOLDERS of alternc/)
# (yes, one day we will be united again ;) )
# and get the TRANSLATED strings from transifex
# then put them at the right places in the repositories
# and commit everything using svn

tx pull -a -f

langs="fr_FR de_DE en_US es_ES pt_BR it_IT nl_NL"

for lang in $langs
do
    echo "doing lang $lang"
    cp "lang/${lang}.po" "bureau/locales/$lang/LC_MESSAGES/alternc"
    sublang=${lang:0:2}
    # merge the po for debconf into the relevant file for the modules : 
    if [ "$lang" != "en_US" ]
    then
	cat "debian/po/${sublang}.po" | sed -e 's/msgstr ""/msgstr "**DUMMY**"/'  >tmp-debconf.po
	msgcat --use-first --less-than=3 --more-than=1 -o tmp.po  "lang/${lang}.po" "tmp-debconf.po"
	rm "tmp-debconf.po"
	mv -f tmp.po "debian/po/${sublang}.po"

	cat "../alternc-mailman/debian/po/${sublang}.po" | sed -e 's/msgstr ""/msgstr "**DUMMY**"/'  >tmp-debconf.po
	msgcat --use-first --less-than=3 --more-than=1 -o tmp.po  "lang/${lang}.po" "tmp-debconf.po"
	rm "tmp-debconf.po"
	mv -f tmp.po "../alternc-mailman/debian/po/${sublang}.po"

	cat "../alternc-mailman/bureau/locales/$lang/LC_MESSAGES/mailman.po" | sed -e 's/msgstr ""/msgstr "**DUMMY**"/'  >tmp-mailman.po
	msgcat --use-first --less-than=3 --more-than=1 -o tmp.po  "lang/${lang}.po" "tmp-mailman.po"
	rm "tmp-mailman.po"
	mv -f tmp.po "../alternc-mailman/bureau/locales/$lang/LC_MESSAGES/mailman.po"
    fi
    echo "done"
done

exit 0

if [ "$1" != "nocommit" ] 
then 
# Now committing 
    git commit -am "Updating language files from Transifex"
    pushd ../alternc-mailman
    git commit -am "Updating language files from Transifex"
    popd
fi


