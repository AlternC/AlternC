all: messages.po */LC_MESSAGES/messages.po */LC_MESSAGES/manual.po


messages.po: ../*/*.php
	touch $@
	xgettext --force-po --omit-header -L PHP --from-code iso-8859-1 -j $^

%/LC_MESSAGES/messages.po: messages.po
	msgmerge -v -U $@ $^

%/LC_MESSAGES/manual.po: manual.po
	msgmerge -v -U $@ $^
