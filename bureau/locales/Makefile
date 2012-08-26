all: mailman_manual.pot mailman.pot */LC_MESSAGES/mailman_manual.po */LC_MESSAGES/mailman.po

mailman.pot: ../*/*.php
	[ -r $@ ] || touch $@
	xgettext --copyright-holder="AlternC Team" --package-name="AlternC" --package-version="2.0" --msgid-bugs-address="i18n@alternc.org"  --force-po -o $@ --keyword=__ --keyword=_  -L PHP -F --from-code UTF-8 -j $^

%/LC_MESSAGES/mailman.po: mailman.pot
	msgmerge -v -U $@ $^

%/LC_MESSAGES/mailman_manual.po: mailman_manual.pot
	msgmerge -v -U $@ $^
