all: messages.po


messages.po: ../*/*.php
	touch $@
	xgettext --force-po --omit-header -L PHP --from-code iso-8859-1 -j $^