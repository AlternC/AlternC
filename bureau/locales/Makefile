all: messages.pot */LC_MESSAGES/messages.po

messages.pot: ../*/*.php ../../squirrelmail/alternc_changepass/*.php ../../awstats/bureau/admin/*.php ../../awstats/bureau/class/*.php ../../ssl/panel/admin/*.php ../../ssl/panel/class/*.php
	[ -r $@ ] || touch $@
	xgettext --copyright-holder="AlternC Team" --package-name="AlternC" --package-version="2.0" --msgid-bugs-address="i18n@alternc.org"  --force-po -o $@ --keyword=__ --keyword=_  -L PHP -F --from-code UTF-8 $^

%/LC_MESSAGES/messages.po: messages.pot
	msgmerge -v -U $@ $^
