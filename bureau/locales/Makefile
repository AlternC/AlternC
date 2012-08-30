all: manual.pot messages.pot */LC_MESSAGES/manual.po */LC_MESSAGES/messages.po

messages.pot: ../*/*.php ../../squirrelmail/alternc-changepass/*.php 
	[ -r $@ ] || touch $@
	xgettext --copyright-holder="AlternC Team" --package-name="AlternC" --package-version="2.0" --msgid-bugs-address="i18n@alternc.org"  --force-po -o $@ --keyword=__ --keyword=_  -L PHP -F --from-code UTF-8 -j $^

%/LC_MESSAGES/messages.po: messages.pot
	msgmerge -v -U $@ $^

%/LC_MESSAGES/manual.po: manual.pot
	msgmerge -v -U $@ $^
