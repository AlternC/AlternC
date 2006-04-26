#!/bin/sh

# AlternC Language file builder
# THIS SCRIPT MUST BE LAUNCHED EACH TIME A MODULE IS ADDED OR REMOVED FROM ALTERNC
# eg: alternc-procmailbuilder, alternc-mailman ...
# $id$
#
# This script build one "alternc.mo" file per language in /var/alternc/bureau/locales/
# Each alternc.mo file is build with msgfmt from a alternc.po file.
# Each alternc.po file is build from a serie of .po files located in 
# /var/alternc/bureau/locales/<lang>/LC_MESSAGES/*.po
# main.txt is included before all others .po to construct a complete alternc.po file.

#  - Advantages : 
#    * allow to add or remove standalone modules to an existing AlternC fluently
#    * only one textdomain is used in all the sources (no need to change it from module to module)
#  - Drawbacks : 
#    * AlternC now requires gettext package to work properly
#    * .mo file must be compiled at each upgrade/install

dolangs() {
    read A
    while [ "$A" ]
    do
	B="$A/LC_MESSAGES"
	cd $B
	rm -f alternc alternc.mo

	# SARGETAG : msgcat exists only for Sarge, not on Woody. Use po.pl on woody, remove it for sarge.
	msgcat --use-first *.po >alternc
        # /usr/share/alternc/install/po.pl

	msgfmt alternc -o alternc.mo
	read A
    done
}

# Apply the function to each language
find /var/alternc/bureau/locales -maxdepth 1 -mindepth 1 -type d -name "*_*" | dolangs

# Relance les apache pour qu'ils vident leur cache GetText
invoke-rc.d apache restart
invoke-rc.d apache-ssl restart

exit 0
