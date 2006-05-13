all: messages.pot */LC_MESSAGES/messages.po */LC_MESSAGES/manual.po


messages.pot: ../*/*.php
	[ -r $@ ] || touch $@
	xgettext --force-po --omit-header -o $@ --keyword=__ --keyword=_  -L PHP --from-code iso-8859-1 -j $^

%/LC_MESSAGES/messages.po: messages.pot
	msgmerge -v -U $@ $^

%/LC_MESSAGES/manual.po: manual.pot
	msgmerge -v -U $@ $^
