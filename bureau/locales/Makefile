all: mailman_manual.pot mailman.pot */LC_MESSAGES/mailman.po */LC_MESSAGES/mailman_manual.po


mailman.pot: ../*/*.php
	[ -r $@ ] || touch $@
	xgettext --force-po --omit-header -o $@ --keyword=__ --keyword=_  -L PHP --from-code iso-8859-1 -j $^

%/LC_MESSAGES/mailman.po: mailman.pot
	msgmerge -v -U $@ $^

%/LC_MESSAGES/mailman_manual.po: mailman_manual.pot
	msgmerge -v -U $@ $^
