all: manual.pot messages.pot */LC_MESSAGES/manual.po */LC_MESSAGES/messages.po


messages.pot: ../*/*.php
	[ -r $@ ] || touch $@
	xgettext --force-po --omit-header -o $@ --keyword=__ --keyword=_  -L PHP --from-code UTF-8 -j $^

%/LC_MESSAGES/messages.po: messages.pot
	msgmerge -v -U $@ $^

%/LC_MESSAGES/manual.po: manual.pot
	msgmerge -v -U $@ $^
