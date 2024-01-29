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

hash tx 2>/dev/null || { echo >&2 "tx is required. Retrieve it from https://github.com/transifex/cli . Stopped."; exit 1; }
hash git 2>/dev/null || { echo >&2 "git is required. Stopped."; exit 1; }

tx pull -a -f

langs="fr_FR de_DE en_US es_ES pt_BR it_IT nl_NL"

for lang in $langs
do
    echo "doing lang $lang"
    cp "lang/${lang}.po" "bureau/locales/$lang/LC_MESSAGES/alternc"
    sublang=$(echo "$lang" | cut -c 1-2)
    # merge the po for debconf into the relevant file for the modules :
    if [ "$lang" != "en_US" ]
    then

    tmppo=$(mktemp)
	sed "debian/po/${sublang}.po" -e 's/msgstr ""/msgstr "**DUMMY**"/' > tmp-debconf.po
	msgcat --use-first --less-than=3 --more-than=1 -o "$tmppo"  "lang/${lang}.po" "tmp-debconf.po"
	rm "tmp-debconf.po"
	mv -f "$tmppo" "debian/po/${sublang}.po"

    tmppo=$(mktemp)
	sed "../alternc-mailman/debian/po/${sublang}.po" -e 's/msgstr ""/msgstr "**DUMMY**"/'  >tmp-debconf.po
	msgcat --use-first --less-than=3 --more-than=1 -o "$tmppo"  "lang/${lang}.po" "tmp-debconf.po"
	rm "tmp-debconf.po"
	mv -f "$tmppo" "../alternc-mailman/debian/po/${sublang}.po"

    tmppo=$(mktemp)
	sed "../alternc-mailman/bureau/locales/$lang/LC_MESSAGES/mailman.po" -e 's/msgstr ""/msgstr "**DUMMY**"/'  >tmp-mailman.po
	msgcat --use-first --less-than=3 --more-than=1 -o "$tmppo"  "lang/${lang}.po" "tmp-mailman.po"
	rm "tmp-mailman.po"
	mv -f "$tmppo" "../alternc-mailman/bureau/locales/$lang/LC_MESSAGES/mailman.po"
    fi
    echo "done"
done

exit 0

# shellcheck disable=SC2317
if [ "$1" != "nocommit" ]
then
# Now committing
    git commit -am "Updating language files from Transifex"
    pushd ../alternc-mailman || exit
    git commit -am "Updating language files from Transifex"
    popd || exit
fi


